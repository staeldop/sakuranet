<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SakuraNet Security</title>
</head>
<body style="margin: 0; padding: 0; background-color: #09090b; font-family: 'Arial', sans-serif; color: #ffffff;">
    
    {{-- ОПРЕДЕЛЯЕМ ТЕКСТЫ НА ОСНОВЕ ТИПА --}}
    @php
        $type = $details['type'] ?? 'login';
        
        $texts = [
            'login' => [
                'title' => 'Подтверждение входа',
                'desc' => 'Мы заметили попытку входа в ваш аккаунт с нового устройства. Если это вы, введите код ниже.',
                'warning' => '⚠️ Если вы не пытались войти, немедленно смените пароль!'
            ],
            'reset' => [
                'title' => 'Восстановление доступа',
                'desc' => 'Был получен запрос на сброс пароля для вашего аккаунта. Используйте этот код для создания нового пароля.',
                'warning' => '⚠️ Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.'
            ],
            'update' => [
                'title' => 'Смена пароля',
                'desc' => 'Вы запросили изменение пароля в настройках профиля. Для подтверждения операции введите код.',
                'warning' => '⚠️ Если это делаете не вы, срочно свяжитесь с поддержкой.'
            ]
        ];

        $current = $texts[$type] ?? $texts['login'];
    @endphp

    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #09090b; padding: 40px 0;">
        <tr>
            <td align="center">
                
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #121214; border: 1px solid #27272a; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                    
                    <tr>
                        <td align="center" style="padding: 30px 0; background: linear-gradient(180deg, rgba(168, 85, 247, 0.1) 0%, rgba(18, 18, 20, 0) 100%);">
                            <h1 style="margin: 0; font-size: 24px; font-weight: bold; color: #ffffff; letter-spacing: 2px;">
                                <span style="color: #a855f7;">🌸</span> SAKURANET
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px;">
                            <h2 style="margin: 0 0 15px 0; font-size: 20px; color: #ffffff;">
                                {{ $current['title'] }}
                            </h2>
                            
                            <p style="margin: 0 0 20px 0; font-size: 15px; color: #a1a1aa; line-height: 1.5;">
                                {{ $current['desc'] }}
                            </p>

                            <div style="background-color: rgba(168, 85, 247, 0.1); border: 1px dashed #a855f7; border-radius: 12px; padding: 20px; text-align: center; margin: 30px 0;">
                                <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #a855f7; display: block;">
                                    {{ $code }}
                                </span>
                            </div>

                            <table width="100%" border="0" cellspacing="0" cellpadding="10" style="background-color: #18181b; border-radius: 8px; margin-bottom: 30px;">
                                <tr>
                                    <td width="30%" style="font-size: 12px; color: #71717a; text-transform: uppercase;">IP Адрес</td>
                                    <td style="font-size: 14px; color: #ffffff; font-family: monospace;">{{ $details['ip'] ?? 'Unknown' }}</td>
                                </tr>
                                <tr>
                                    <td width="30%" style="font-size: 12px; color: #71717a; text-transform: uppercase;">Браузер</td>
                                    <td style="font-size: 14px; color: #ffffff;">{{ $details['browser'] ?? 'Unknown Device' }}</td>
                                </tr>
                                <tr>
                                    <td width="30%" style="font-size: 12px; color: #71717a; text-transform: uppercase;">Время</td>
                                    <td style="font-size: 14px; color: #ffffff;">{{ now()->format('d.m.Y H:i') }}</td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 30px 0; font-size: 13px; color: #ef4444; line-height: 1.4;">
                                {{ $current['warning'] }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 20px; background-color: #0e0e10; border-top: 1px solid #27272a;">
                            <p style="margin: 0; font-size: 12px; color: #52525b;">
                                &copy; {{ date('Y') }} SakuraNet Systems.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>