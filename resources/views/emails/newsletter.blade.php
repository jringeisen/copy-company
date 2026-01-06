<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $post->title }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #f4f4f7;
        }

        /* Content styles */
        .content-block {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 17px;
            line-height: 1.7;
            color: #3d4852;
        }
        .content-block h1 {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.3;
            color: #1a1a1a;
            margin: 0 0 16px 0;
        }
        .content-block h2 {
            font-size: 22px;
            font-weight: 600;
            line-height: 1.4;
            color: #1a1a1a;
            margin: 32px 0 12px 0;
        }
        .content-block h3 {
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
            color: #1a1a1a;
            margin: 24px 0 8px 0;
        }
        .content-block p {
            margin: 0 0 20px 0;
        }
        .content-block a {
            color: {{ $brand->primary_color ?? '#4f46e5' }};
            text-decoration: underline;
        }
        .content-block ul, .content-block ol {
            margin: 0 0 20px 0;
            padding-left: 24px;
        }
        .content-block li {
            margin-bottom: 8px;
        }
        .content-block blockquote {
            border-left: 4px solid {{ $brand->primary_color ?? '#4f46e5' }};
            margin: 20px 0;
            padding: 12px 20px;
            background-color: #f8fafc;
            color: #5c6873;
            font-style: italic;
        }
        .content-block pre {
            background-color: #1e293b;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'SF Mono', Monaco, 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 20px 0;
        }
        .content-block code {
            background-color: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'SF Mono', Monaco, 'Courier New', monospace;
            font-size: 14px;
            color: #e11d48;
        }
        .content-block pre code {
            background: none;
            padding: 0;
            color: inherit;
        }
        .content-block img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }
        .content-block hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 32px 0;
        }

        /* Responsive */
        @media only screen and (max-width: 620px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .fluid {
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
            }
            .stack-column {
                display: block !important;
                width: 100% !important;
            }
            .mobile-padding {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7;">
    {{-- Preview text (hidden) --}}
    @if($previewText)
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        {{ $previewText }}
        {{-- Pad with whitespace to push other content out of preview --}}
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>
    @endif

    {{-- Outer wrapper table --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f7;">
        <tr>
            <td style="padding: 40px 16px;">

                {{-- Email container (max 600px, centered) --}}
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" align="center" class="email-container" style="margin: 0 auto; max-width: 600px;">

                    {{-- Header --}}
                    <tr>
                        <td style="padding: 0 0 32px 0; text-align: center;">
                            <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 20px; font-weight: 600; color: {{ $brand->primary_color ?? '#4f46e5' }};">
                                {{ $brand->name }}
                            </span>
                            @if($brand->tagline)
                            <br>
                            <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; color: #6b7280;">
                                {{ $brand->tagline }}
                            </span>
                            @endif
                        </td>
                    </tr>

                    {{-- Main content card --}}
                    <tr>
                        <td>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">

                                {{-- Post title section --}}
                                <tr>
                                    <td class="mobile-padding" style="padding: 40px 48px 0 48px;">
                                        <h1 style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 28px; font-weight: 700; line-height: 1.3; color: #1a1a1a; margin: 0 0 8px 0;">
                                            {{ $post->title }}
                                        </h1>
                                        @if($post->excerpt)
                                        <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 18px; line-height: 1.5; color: #6b7280; margin: 0 0 24px 0;">
                                            {{ $post->excerpt }}
                                        </p>
                                        @endif
                                        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0 0 0;">
                                    </td>
                                </tr>

                                {{-- Post content --}}
                                <tr>
                                    <td class="mobile-padding content-block" style="padding: 32px 48px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 17px; line-height: 1.7; color: #3d4852;">
                                        {!! $post->content_html !!}
                                    </td>
                                </tr>

                                {{-- CTA Button --}}
                                <tr>
                                    <td class="mobile-padding" style="padding: 0 48px 40px 48px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;">
                                            <tr>
                                                <td style="border-radius: 8px; background-color: {{ $brand->primary_color ?? '#4f46e5' }};">
                                                    <a href="{{ route('public.blog.show', ['brand' => $brand->slug, 'post' => $post->slug]) }}" style="display: inline-block; padding: 14px 28px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">
                                                        Read on our blog &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 32px 16px; text-align: center;">
                            <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.6; color: #9ca3af; margin: 0 0 8px 0;">
                                You're receiving this because you subscribed to {{ $brand->name }}.
                            </p>
                            <p style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.6; color: #9ca3af; margin: 0;">
                                <a href="{{ $unsubscribeUrl }}" style="color: #9ca3af; text-decoration: underline;">Unsubscribe</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
