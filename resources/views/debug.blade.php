<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
</head>
<body class="portal yellowbg">
    @php
        (new Symfony\Component\VarDumper\VarDumper())->dump($var->form);
    @endphp
</body>
</html>