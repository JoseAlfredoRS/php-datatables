<!doctype html>
<html lang="es">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Libreria Datatable CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <!-- Libreria Fontawesome CSS -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css">

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
                <li class="nav-item active">
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
            <h3>Datatables Server-side ejemplo 1</h3>
            <p>Tabla sucursal</p>
            <table id="example" class="table table-striped table-bordered" style="width:100%">
                <thead class="bg-primary text-white">
                    <tr>
                        <th scope="col" width="5%">#</th>
                        <th scope="col" width="17%">Nombre</th>
                        <th scope="col" width="17%">Ciudad</th>
                        <th scope="col" width="40%">Direccion</th>
                        <th scope="col" width="10%">Cuenta</th>
                        <th scope="col" width="10%">Estado</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Librerias Datatable JS -->
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            listado();
        });

        var table;

        var listado = () => {
            table = $('#example').DataTable({
                'processing': true,
                'serverSide': true,
                'sAjaxSource': './server/server-example-1.php',
            });
        }
    </script>
</body>

</html>