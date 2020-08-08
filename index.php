<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <title>Datatable</title>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark ">
        <a class="navbar-brand" href="index.php">JLDeveloper</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="example-1.php">Ejemplo 1</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="example-2.php">Ejemplo 2</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="example-3.php">Ejemplo 3</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="example-4.php">Ejemplo 4</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="mt-4">
            <h3>Datatables Server-side</h3>
            <div class="mt-4">
                <p>Base de datos : db_mvc</p>
                <p>Estado :
                    <?php
                    try {
                        require_once './src/autoload.php';
                        require_once './classes/HTML5.php';
                        $db = new Database;
                        $db->testPDO();
                        echo HTML5::span([
                            'class' => 'font-weight-bold text-success',
                            'text'  => 'Conectado',
                        ]);
                    } catch (Exception $th) {
                        echo $th->getMessage();
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>