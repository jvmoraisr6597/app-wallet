<!DOCTYPE html>
<html>
<head>
    <!-- Carregue os arquivos de asset compilados pelo Laravel Mix -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
    <!-- Exemplo de uso do componente Vue no Blade -->
    <div id="app">
        <example-component></example-component>
    </div>
</body>
</html>
