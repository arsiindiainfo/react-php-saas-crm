<?php

namespace App\Libraries;

/**
 * Purely transactional invite-email markup — deliberately NOT a
 * marketing-style layout with cross-promotional links. A from/link
 * domain mismatch, or a verify/account email stuffed with sales
 * content, reads as a phishing signal to spam filters independent of
 * sender authentication (see the same lesson learned fixing
 * secure-cloud-document-manager and email-campaign-delivery-tracker's
 * verification emails this session).
 */
class EmailTemplate
{
    public static function renderInvite(string $name, string $email, string $temporaryPassword): string
    {
        $safeName     = esc($name, 'html');
        $safeEmail    = esc($email, 'html');
        $safePassword = esc($temporaryPassword, 'html');
        $year         = date('Y');
        $logoUrl      = 'https://www.arsiindiainfo.com/assets/images/logo-horizontal.png';
        $loginUrl     = 'https://demo.arsiindiainfo.com/login';

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>Your Arsi CRM account</title>
            </head>
            <body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f7fb;">
                <tr>
                  <td align="center" style="padding:30px 15px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                           style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 30px rgba(31,59,93,.08);">

                      <tr>
                        <td style="padding:28px 36px;border-bottom:1px solid #edf1f7;background:#ffffff;">
                          <img src="{$logoUrl}" alt="Arsi India Info" width="280"
                               style="display:block;width:280px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;">
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:36px;">
                          <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#102a56;">
                            Your Arsi CRM account is ready
                          </h1>

                          <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#52627a;">
                            Hi {$safeName}, an account has been created for you on Arsi CRM. Sign in with the
                            credentials below.
                          </p>

                          <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                 style="margin:0 0 22px;border:1px solid #e5ebf3;border-radius:12px;background:#fbfdff;">
                            <tr>
                              <td style="padding:14px 18px;font-size:13px;color:#64748b;width:110px;">Email</td>
                              <td style="padding:14px 18px;font-size:14px;font-weight:600;color:#0f172a;">{$safeEmail}</td>
                            </tr>
                            <tr>
                              <td style="padding:0 18px 14px;font-size:13px;color:#64748b;">Temporary password</td>
                              <td style="padding:0 18px 14px;font-size:14px;font-weight:600;color:#0f172a;font-family:monospace;">{$safePassword}</td>
                            </tr>
                          </table>

                          <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                              <td style="border-radius:10px;background:#1769e8;">
                                <a href="{$loginUrl}"
                                   style="display:inline-block;padding:14px 26px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">
                                  Sign In to Arsi CRM
                                </a>
                              </td>
                            </tr>
                          </table>

                          <p style="margin:20px 0 0;font-size:12px;line-height:1.6;color:#8492a6;">
                            This is a demo account &mdash; change this password after signing in.
                          </p>
                        </td>
                      </tr>

                      <tr>
                        <td style="padding:20px 36px;text-align:center;background:#f7faff;border-top:1px solid #e9eff7;">
                          <p style="margin:0;font-size:11px;line-height:1.6;color:#9aa7b8;">
                            This is an automated message. If you weren't expecting this, you can ignore it.
                          </p>
                          <p style="margin:10px 0 0;font-size:11px;color:#a2adbb;">
                            &copy; {$year} Arsi India Info. All rights reserved.
                          </p>
                        </td>
                      </tr>

                    </table>
                  </td>
                </tr>
              </table>
            </body>
            </html>
            HTML;
    }
}
