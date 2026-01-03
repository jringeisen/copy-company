<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .brand-name {
            font-size: 24px;
            font-weight: bold;
            color: {{ $brand->primary_color ?? '#4f46e5' }};
        }
        .post-title {
            font-size: 28px;
            font-weight: bold;
            color: #111;
            margin-bottom: 10px;
        }
        .post-excerpt {
            color: #666;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .post-content {
            margin-bottom: 30px;
        }
        .post-content img {
            max-width: 100%;
            height: auto;
        }
        .read-more {
            display: inline-block;
            padding: 12px 24px;
            background-color: {{ $brand->primary_color ?? '#4f46e5' }};
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .unsubscribe {
            color: #999;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    @if($previewText)
    <span style="display:none;font-size:1px;color:#fff;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
        {{ $previewText }}
    </span>
    @endif

    <div class="header">
        <div class="brand-name">{{ $brand->name }}</div>
    </div>

    <h1 class="post-title">{{ $post->title }}</h1>

    @if($post->excerpt)
    <p class="post-excerpt">{{ $post->excerpt }}</p>
    @endif

    <div class="post-content">
        {!! $post->content_html !!}
    </div>

    <p style="text-align: center;">
        <a href="{{ route('public.blog.show', ['brand' => $brand->slug, 'post' => $post->slug]) }}" class="read-more">
            Read on our blog
        </a>
    </p>

    <div class="footer">
        <p>
            You're receiving this email because you subscribed to {{ $brand->name }}.
        </p>
        <p>
            <a href="{{ $unsubscribeUrl }}" class="unsubscribe">Unsubscribe</a>
        </p>
    </div>
</body>
</html>
