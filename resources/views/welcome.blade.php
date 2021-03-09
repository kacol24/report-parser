<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl"
          crossorigin="anonymous">
</head>
<body class="bg-light">
<main class="position-absolute w-100 h-100 d-flex align-items-center justify-contents-center" style="top: 0;left: 0;">
    <div class="bg-white p-3 rounded shadow-sm mx-auto">
        <form action="/" method="post" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <input type="file" class="form-control" name="file">
            </div>
            <div class="mb-3">
                <select class="form-select" name="report_type">
                    <option value="majoo">
                        Majoo
                    </option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary float-end">
                Submit
            </button>
        </form>
    </div>
</main>
</body>
</html>
