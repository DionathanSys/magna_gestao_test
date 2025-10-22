<!DOCTYPE html>
<html lang="pt_BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload PDF</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Extrair dados de PDF</h1>
        
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form action="{{ route('upload.pdf') }}" method="POST" enctype="multipart/form-data" class="mt-4">
            @csrf
            <div class="mb-3">
                <label for="pdfFile" class="form-label">Escolha o arquivo PDF</label>
                <input type="file" class="form-control" id="pdfFile" name="pdfFile" accept="application/pdf" required>
                <div class="form-text">Tamanho máximo: 2MB. Apenas arquivos PDF são aceitos.</div>
            </div>
            <button type="submit" class="btn btn-primary">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                Processar PDF
            </button>
        </form>
        <?php echo '<a href="' . App\Filament\Resources\DocumentoFretes\DocumentoFreteResource::getUrl() . '">Acessar Documentos de Frete</a>'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            const spinner = button.querySelector('.spinner-border');
            
            button.disabled = true;
            spinner.classList.remove('d-none');
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...';
        });
    </script>
</body>
</html>
