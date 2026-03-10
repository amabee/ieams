<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Your {{ config('app.name') }} Account</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        a { color: #696cff; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .fluid { max-width: 100% !important; height: auto !important; }
            .stack-column, .stack-column-center { display: block !important; width: 100% !important; max-width: 100% !important; }
            .pad-sm { padding: 24px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;word-spacing:normal;">

{{-- Outer wrapper --}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f3f4f6;">
    <tr>
        <td align="center" style="padding:40px 16px;">

            {{-- Email container --}}
            <table class="email-container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="560" style="margin:0 auto;">

                {{-- ── Icon + Headline ── --}}
                <tr>
                    <td style="padding:0 0 24px 0;">

                        {{-- App icon --}}
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td width="48" height="48" bgcolor="#696cff" style="background-color:#696cff;border-radius:50%;text-align:center;vertical-align:middle;font-size:22px;line-height:48px;">
                                    ⚡
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <tr>
                    <td style="padding:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:26px;font-weight:800;color:#111827;line-height:1.3;">
                        Your account is ready,<br>{{ explode(' ', $employeeName)[0] }}.
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 0 32px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#6b7280;line-height:1.75;">
                        Welcome to <a href="{{ $loginUrl }}" style="color:#696cff;font-weight:600;text-decoration:none;">{{ config('app.name') }}</a>.
                        Your employee system account has been created by the HR team.
                        Use the credentials below to log in for the first time.
                    </td>
                </tr>

                {{-- ── Credentials card ── --}}
                <tr>
                    <td style="padding:0 0 24px 0;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">

                            {{-- Card header --}}
                            <tr>
                                <td bgcolor="#f9fafb" style="background-color:#f9fafb;padding:14px 20px;border-bottom:1px solid #e5e7eb;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;color:#374151;letter-spacing:0.1px;">
                                    Account credentials
                                </td>
                            </tr>

                            {{-- Full Name row --}}
                            <tr>
                                <td style="padding:14px 20px 0 20px;border-bottom:1px solid #f3f4f6;">
                                    <p style="margin:0 0 3px 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Full Name</p>
                                    <p style="margin:0 0 14px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#111827;">{{ $employeeName }}</p>
                                </td>
                            </tr>

                            {{-- Login URL row --}}
                            <tr>
                                <td style="padding:14px 20px 0 20px;border-bottom:1px solid #f3f4f6;">
                                    <p style="margin:0 0 3px 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Login URL</p>
                                    <p style="margin:0 0 14px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;">
                                        <a href="{{ $loginUrl }}" style="color:#696cff;text-decoration:none;">{{ $loginUrl }}</a>
                                    </p>
                                </td>
                            </tr>

                            {{-- Email row --}}
                            <tr>
                                <td style="padding:14px 20px 0 20px;border-bottom:1px solid #f3f4f6;">
                                    <p style="margin:0 0 3px 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Email Address</p>
                                    <p style="margin:0 0 14px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;color:#111827;">{{ $loginEmail }}</p>
                                </td>
                            </tr>

                            {{-- Password row --}}
                            <tr>
                                <td style="padding:14px 20px 20px 20px;">
                                    <p style="margin:0 0 3px 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Temporary Password</p>
                                    <p style="margin:0;font-family:'Courier New',Courier,monospace;font-size:15px;font-weight:700;color:#4f46e5;background-color:#eff0ff;display:inline-block;padding:4px 10px;border-radius:6px;letter-spacing:0.5px;">{{ $plainPassword }}</p>
                                </td>
                            </tr>

                            {{-- CTA button row --}}
                            <tr>
                                <td bgcolor="#ffffff" style="background-color:#ffffff;padding:20px;border-top:1px solid #e5e7eb;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td align="center" bgcolor="#696cff" style="background-color:#696cff;border-radius:8px;">
                                                <a href="{{ $loginUrl }}"
                                                   target="_blank"
                                                   style="display:block;padding:13px 24px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;letter-spacing:0.2px;">
                                                    Log in to your account
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>

                {{-- ── Security tip card ── --}}
                <tr>
                    <td style="padding:0 0 28px 0;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:12px;">
                            <tr>
                                {{-- Shield icon cell --}}
                                <td width="68" valign="middle" style="padding:18px 0 18px 18px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td width="44" height="44" bgcolor="#eff0ff" style="background-color:#eff0ff;border-radius:50%;border:2px solid #696cff;text-align:center;vertical-align:middle;font-size:20px;line-height:40px;">
                                                🔒
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                {{-- Tip text cell --}}
                                <td valign="middle" style="padding:18px 18px 18px 12px;">
                                    <p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;color:#111827;">
                                        Your account security:
                                        <span style="color:#696cff;">Change your password</span>
                                    </p>
                                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6b7280;line-height:1.6;">
                                        This is a system-generated temporary password.
                                        <a href="{{ $loginUrl }}" style="color:#696cff;font-weight:600;text-decoration:none;">Please update it immediately</a>
                                        after your first login to keep your account safe.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ── Divider ── --}}
                <tr>
                    <td style="padding:0 0 24px 0;border-top:1px solid #f3f4f6;font-size:0;line-height:0;">&nbsp;</td>
                </tr>

                {{-- ── Footer ── --}}
                <tr>
                    <td style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#9ca3af;line-height:1.7;">
                        Sent by the {{ config('app.name') }} HR Team<br>
                        This is an automated message &mdash; please do not reply to this email.<br>
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </td>
                </tr>

            </table>
            {{-- /Email container --}}

        </td>
    </tr>
</table>

</body>
</html>
