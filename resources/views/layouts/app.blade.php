<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generic DB</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>


</head>
<body>
@if(Auth::check())
    <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand logo" href="/">Generic DB.</a>
        <a class="navbar-brand link" href="{{ route('collections.create') }}">Add New Object</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto">

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle link" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ Auth::user()->name }} <!-- Display user email -->
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="{{ route('profile.index') }}">Profile</a>
                            @if(Auth::user()->is_admin == 1)
                            <a class="dropdown-item" href="{{ route('activity.logs') }}">Activity Logs</a>

                            <a class="dropdown-item" href="{{ route('admin.users') }}">Users list</a>
                            @endif

                            <div class="dropdown-divider"></div>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item btn">Logout</button>
                            </form>
                        </div>
                    </li>

            </ul>
        </div>
    </nav>
    @endif


        @yield('content')
    
</body>

<script src="{{ asset('js/app.js') }}"></script>

</html>
