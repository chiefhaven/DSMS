@props(['url'])
<tr>
    <td class="header" align="center">
        <table class="email-brand" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="email-brand-cell">
                    <table cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                            <td class="email-brand-mark" width="42" height="42" valign="middle">DS</td>
                            <td style="padding-left: 14px;">
                                <p class="email-brand-kicker">Driving School Management System</p>
                                <a href="{{ $url }}" style="color: #fff; text-decoration: none;">
                                    <span class="email-brand-title">{{ config('app.school_name', config('app.name', 'Mbira DSMS')) }}</span>
                                </a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>
