<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{% yield meta_description %}">
    <title>{% yield title %}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #242525;
        }

    </style>
</head>
<body>

    <main>
        {% yield content %}
    </main>




</body>
</html>