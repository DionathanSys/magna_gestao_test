<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
    <style>
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        body { display: grid; min-height: 100vh; margin: 0; place-items: center; padding: 1.5rem; box-sizing: border-box; background: #07111f; color: #e2e8f0; }
        main { width: min(100%, 30rem); padding: 2rem; border: 1px solid #1e3a50; border-radius: 1.25rem; background: #0d1b2a; text-align: center; box-shadow: 0 1.5rem 4rem rgb(0 0 0 / 25%); }
        .mark { display: grid; width: 3rem; height: 3rem; margin: 0 auto 1rem; place-items: center; border-radius: 1rem; background: #143b4d; color: #5eead4; font-size: 1.4rem; }
        h1 { margin: 0; color: #f8fafc; font-size: 1.35rem; }
        p { margin: .75rem 0 0; color: #94a3b8; line-height: 1.6; }
    </style>
</head>
<body>
    <main>
        <div class="mark">!</div>
        <h1>{{ $titulo }}</h1>
        <p>{{ $mensagem }}</p>
    </main>
</body>
</html>
