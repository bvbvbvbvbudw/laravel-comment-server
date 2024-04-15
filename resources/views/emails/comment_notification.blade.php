<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<li class="item" style="list-style: none; margin: 10px;">
    <div class="header__item" style="background-color: #f5f6f7; padding: 5px; display: flex; align-items: center; gap: 10px;">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/59/User-avatar.svg/480px-User-avatar.svg.png"
             alt="Avatar" style="margin-left: 10px; width: 40px; height: 40px;">
        <p><strong>{{ $comment->user[0]->username }}</strong></p>
        <p>{{ $comment->created_at }}</p>
    </div>
    <div style="margin: 10px;">
        {!! $comment->text !!}
    </div>
</li>
</body>
</html>
