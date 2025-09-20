<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login - EZToll Admin</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .login-container {
            background-image: url('https://images.unsplash.com/photo-1545159227-d4e6b922c067?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        .input-icon {
            color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen login-container flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full glass-effect rounded-xl p-8">
            <div class="text-center">
                <div class="flex justify-center">
                    <div class="bg-blue-600 rounded-full p-4 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                        </svg>
                    </div>
                </div>
                <h1 class="mt-4 text-3xl font-bold text-blue-600">EZToll Admin</h1>
                <h2 class="mt-2 text-xl font-medium text-gray-700">Login ke Panel Admin</h2>
                <p class="mt-2 text-sm text-gray-500">Masukkan kredensial Anda untuk melanjutkan</p>
            </div>
            
            @if($errors->any())
                <div class="mt-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow-sm" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <ul class="list-disc list-inside text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            
            <form class="mt-8 space-y-6" action="{{ route('admin.login.submit') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                            class="pl-10 appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg 
                            placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 
                            focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm" 
                            placeholder="Email" value="{{ old('email') }}">
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required 
                            class="pl-10 appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg 
                            placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 
                            focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm" 
                            placeholder="Password">
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember" type="checkbox" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded 
                            transition duration-150 ease-in-out">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                            Ingat saya
                        </label>
                    </div>
                </div>
                
                <div>
                    <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent 
                        text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-500 to-blue-700 
                        hover:from-blue-600 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 
                        focus:ring-blue-500 transition duration-150 ease-in-out shadow-md hover:shadow-lg">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-300 group-hover:text-blue-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        Masuk ke Panel Admin
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-6">
                <a href="/" class="font-medium text-blue-600 hover:text-blue-500 transition duration-150 ease-in-out">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda
                </a>
            </div>
            
            <div class="mt-8 text-center text-xs text-gray-500">
                &copy; {{ date('Y') }} EZToll. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>