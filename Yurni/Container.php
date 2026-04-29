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
     * 
     * @param Reflector $reflection كائن Reflection
     * @return array مصفوفة المعاملات الجاهزة للحقن
     */
    public function generateArgs(\Reflector $reflection)
    {
        $parameters = [];

        if ($reflection instanceof \ReflectionFunctionAbstract) {
            $reflect = $reflection;
        } elseif ($reflection instanceof \ReflectionClass) {
            $reflect = $reflection->getConstructor();
        } else {
            return $parameters;
        }

        if (!$reflect) {
            return $parameters;
        }

        foreach ($reflect->getParameters() as $key => $param) {
            if ($param->getType() && !$param->getType()->isBuiltin()) {
                $class = new ReflectionClass($param->getType()->getName());
            } else {
                $class = null;
            }

            if (!is_null($class) && array_key_exists($class->getName(), $this->args)) {
                $parameters[] = $this->args[$class->getName()];
            } else if (!is_null($class)) {
                $cl = $class->getName();
                try {
                    $parameters[] = $this->get($cl);
                } catch (\Exception $e) {
                    if ($param->isOptional()) {
                        $parameters[] = $param->getDefaultValue();
                    } else {
                        throw $e;
                    }
                }
            } else {
                // للأنواع الأساسية (Builtin) كالـ int و string
                if (array_key_exists($param->getName(), $this->args)) {
                    $parameters[] = $this->args[$param->getName()];
                } elseif ($param->isOptional()) {
                    $parameters[] = $param->getDefaultValue();
                } else {
                    // إذا لم نجد المتغير، نضع null أو نأخذ أول قيمة غير مستخدمة (يفضل null لمنع الأخطاء العشوائية)
                    $parameters[] = null;
                }
            }
        }

        return $parameters;
    }

    /**
     * تحويل الدالة المجهولة (Closure) إلى ReflectionFunction
     * 
     * @param callable $callback
     * @return ReflectionFunction
     */
    protected function callable($callback)
    {
        return new ReflectionFunction($callback);
    }

    /**
     * تنفيذ دالة أو كلاس بعد حقن جميع تبعياته
     * 
     * @param callable|array $callback الدالة أو الكلاس المراد تنفيذه
     * @return mixed نتيجة التنفيذ
     * @throws \Exception
     */
    public function call($callback)
    {
        if (is_array($callback)) {
            if (isset($callback[1])) {
                list($class, $method) = $callback;
                $reflect = new ReflectionMethod($class, $method);
                $callback[0] = new ReflectionClass($callback[0]);
                $constructor = $callback[0]->getConstructor();

                $args = [];
                if (!empty($constructor)) {
                    $args = $this->generateArgs($callback[0]);
                }
                $callback[0] = $callback[0]->newInstanceArgs($args ?? []);
            }

            $args = $this->generateArgs($reflect) ?? [];
            return call_user_func_array($callback, $args);

        } elseif (is_callable($callback)) {
            $reflect = $this->callable($callback);
            $args = $this->generateArgs($reflect) ?? [];
            return call_user_func_array($callback, $args);

        } else {
            throw new \Exception("Cannot resolve callback in Container");
        }
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
