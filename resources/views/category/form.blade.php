<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nueva Categoria</title>
</head>

<body>
    <form action="{{ route('category.save') }}" method="POST">
        @csrf
        Nombre: <input type="text" name="name"><br><br>
        Descripcion: <br>
        <textarea name="description" cols="30" rows="10"></textarea><br>
        Ubicacion: <input type="text" name="location"><br>
        Estado: <br>
        <input type="radio" name="state" value="activo">Activo
        <input type="radio" name="state" value="inactivo">Inactivo
        <input type="radio" name="state" value="eliminado">Eliminado
        <br><br><input type="submit" name="save">
    </form>
</body>

</html>
