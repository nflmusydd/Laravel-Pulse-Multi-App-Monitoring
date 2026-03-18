<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-6 rounded shadow-md w-full max-w-sm">
    <h2 class="text-2xl font-bold mb-4 text-center">Login</h2>

    @if ($errors->any())
    <div class="mb-4 text-red-600 text-sm">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ url('/login') }}">
        @csrf

        <div class="mb-4">
            <label class="block text-sm">Email</label>
            <input type="email" name="email" class="w-full border p-2 rounded" required autofocus>
        </div>

        <div class="mb-4">
            <label class="block text-sm">Password</label>
            <input type="password" name="password" class="w-full border p-2 rounded" required>
        </div>

        <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Login</button>
    </form>
</div>

</body>
</html>
