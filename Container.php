<?php
namespace yurni;

use Reflector;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use yurni\Exception\NotFoundException;
use yurni\Exception\RuntimeException;
use yurni\ContainerInterface;

/**
 * حاوية الحقن التلقائي (Dependency Injection Container)
 * مسؤولة عن إنشاء الكائنات تلقائياً وحقن التبعيات الخاصة بها بناءً على الـ Reflection.
 */
class Container implements ContainerInterface
{

    /**
     * @var array المدخلات التي سيتم حقنها
     */
    protected array $args = [];

    /**
     * الحصول على المدخلات المحقونة
     * 
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @var array الكائنات التي تم إنشاؤها مسبقاً (Singletons)
     */
    protected array $instances = [];

    /**
     * @var array الدوال المسجلة لإنشاء الكائنات
     */
    protected array $bindings = [];

    /**
     * حقن مصفوفة من المتغيرات لاستخدامها لاحقاً
     * 
     * @param array $args مصفوفة المتغيرات
     * @return self
     */
    public function injectArgs($args)
    {
        foreach ($args as $key => $val) {
            if (!isset($this->args[$key])) {
                $this->args[$key] = $val;
            }
        }
        return $this;
    }

    /**
     * استخراج وتوليد المعاملات المطلوبة لدالة أو كلاس معين
     * بناءً على الـ Type Hinting باستخدام الـ Reflection
     */
    public function generateArgs(\Reflector $reflection)
    {
        $parameters = [];
        $reflect = $this->getReflectionSignature($reflection);

        if (!$reflect) {
            return $parameters;
        }

        foreach ($reflect->getParameters() as $param) {
            $parameters[] = $this->resolveParameter($param);
        }

        return $parameters;
    }

    private function getReflectionSignature(\Reflector $reflection): ?\ReflectionFunctionAbstract
    {
        if ($reflection instanceof \ReflectionFunctionAbstract) {
            return $reflection;
        }
        if ($reflection instanceof \ReflectionClass) {
            return $reflection->getConstructor();
        }
        return null;
    }

    private function resolveParameter(\ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if ($type && !$type->isBuiltin()) {
            return $this->resolveClassDependency($param, $type->getName());
        }

        return $this->resolvePrimitiveDependency($param);
    }

    private function resolveClassDependency(\ReflectionParameter $param, string $className): mixed
    {
        if (array_key_exists($className, $this->args)) {
            return $this->args[$className];
        }

        try {
            return $this->get($className);
        } catch (\Exception $e) {
            if ($param->isOptional()) {
                return $param->getDefaultValue();
            }
            throw $e;
        }
    }

    private function resolvePrimitiveDependency(\ReflectionParameter $param): mixed
    {
        if (array_key_exists($param->getName(), $this->args)) {
            return $this->args[$param->getName()];
        }

        if ($param->isOptional()) {
            return $param->getDefaultValue();
        }

        $location = $param->getDeclaringClass() ? $param->getDeclaringClass()->getName() : 'Closure';
        throw new RuntimeException("Cannot resolve required parameter \${$param->getName()} in {$location}.");
    }

    /**
     * تحويل الدالة المجهولة (Closure) إلى ReflectionFunction
     */
    protected function callable($callback): ReflectionFunction
    {
        return new ReflectionFunction($callback);
    }

    /**
     * تنفيذ دالة أو كلاس بعد حقن جميع تبعياته
     */
    public function call($callback)
    {
        if (is_array($callback)) {
            return $this->callArrayCallback($callback);
        }

        if (is_callable($callback)) {
            return $this->callClosureCallback($callback);
        }

        throw new \Exception("Cannot resolve callback in Container.");
    }

    private function callArrayCallback(array $callback): mixed
    {
        if (!isset($callback[1])) {
            throw new \Exception("Invalid array callback provided.");
        }

        [$classOrObject, $method] = $callback;

        if (is_string($classOrObject)) {
            $classOrObject = $this->hasInstance($classOrObject) ? $this->instances[$classOrObject] : $this->get($classOrObject);
            $callback[0] = $classOrObject;
        }

        $reflect = new ReflectionMethod($classOrObject, $method);
        $args = $this->generateArgs($reflect) ?? [];

        return call_user_func_array($callback, $args);
    }

    private function callClosureCallback(callable $callback): mixed
    {
        $reflect = $this->callable($callback);
        $args = $this->generateArgs($reflect) ?? [];

        return call_user_func_array($callback, $args);
    }

    /**
     * استدعاء كائن من الحاوية عبر اسمه
     * 
     * @param string $id اسم الكلاس
     * @return mixed الكائن المطلوب
     * @throws NotFoundException
     */
    public function get(string $id)
    {
        if ($this->hasInstance($id)) {
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            throw new NotFoundException("Dependency '{$id}' not found in container.");
        }

        if (isset($this->bindings[$id]) && is_callable($this->bindings[$id])) {
            $this->instances[$id] = call_user_func($this->bindings[$id], $this);
            return $this->instances[$id];
        }

        if (class_exists($id)) {
            $reflect = new ReflectionClass($id);

            if (!$reflect->isInstantiable()) {
                throw new RuntimeException("Class '{$id}' is not instantiable.");
            }

            $constructor = $reflect->getConstructor();

            if (is_null($constructor)) {
                $this->instances[$id] = new $id();
            } else {
                $args = $this->generateArgs($reflect);
                $this->instances[$id] = $reflect->newInstanceArgs($args);
            }
            return $this->instances[$id];
        }

        throw new NotFoundException("Dependency '{$id}' not found in container.");
    }

    /**
     * التحقق من وجود الكائن في الحاوية
     * 
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]) || class_exists($id);
    }

    /**
     * التحقق مما إذا كان الكائن قد تم إنشاؤه مسبقاً (Singleton)
     * 
     * @param string $id
     * @return bool
     */
    public function hasInstance(string $id): bool
    {
        return isset($this->instances[$id]);
    }

    /**
     * ربط اسم معين بدالة لإنشائه لاحقاً
     * 
     * @param string $id
     * @param callable $concrete
     * @return void
     */
    public function bind(string $id, callable $concrete): void
    {
        $this->bindings[$id] = $concrete;
    }

    /**
     * تسجيل كائن جاهز في الحاوية (Singleton)
     * 
     * @param string $id
     * @param mixed $instance
     * @return void
     */
    public function instance(string $id, $instance): void
    {
        $this->instances[$id] = $instance;
    }
}
