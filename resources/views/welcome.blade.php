<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamesSpot - Your Gaming Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Roboto', sans-serif;
            color: white;
        }
        
        .background {
            background-image: url('https://images.unsplash.com/photo-1542751371-adc38448a05e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }
        
        .background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }
        
        .content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            padding: 20px;
        }
        
        h1 {
            font-family: 'Press Start 2P', cursive;
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 3px 3px 0 #000;
        }
        
        p {
            font-size: 1.5rem;
            margin-bottom: 40px;
        }
        
        .login-btn {
            display: inline-block;
            padding: 15px 30px;
            background: #dc8539;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .login-btn:hover {
            background: #f39d51;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            
            p {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="background">
        <div class="content">
            <h1>GamesSpot</h1>
            <p>Enter a world of endless gaming adventures</p>
            <a href="{{ route('manager.login') }}" class="login-btn">Login</a>
        </div>
    </div>
</body>
</html>