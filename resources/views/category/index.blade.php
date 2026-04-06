<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Lista de Categorias</title>
</head>

<body>
    <table border="1">
        <thead>
            <tr>
                <td>Name</td>
                <td>Description</td>
                <td>Loaction</td>
                <td>state</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->description }}</td>
                    <td>{{ $category->location }}</td>
                    <td>{{ $category->state }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
