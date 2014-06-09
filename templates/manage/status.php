<?php
/*
*    simpleSAMLphp-oauth2server is an OAuth 2.0 authorization and resource server in the form of a simpleSAMLphp module
*
*    Copyright (C) 2014  Bjorn R. Jensen
*
*    This library is free software; you can redistribute it and/or
*    modify it under the terms of the GNU Lesser General Public
*    License as published by the Free Software Foundation; either
*    version 2.1 of the License, or (at your option) any later version.
*
*    This library is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
*    Lesser General Public License for more details.
*
*    You should have received a copy of the GNU Lesser General Public
*    License along with this library; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*
*/

$this->data['header'] = $this->t('{oauth2server:oauth2server:status_header}');

$this->includeAtTemplateBase('includes/header.php');
?>
    <form action="<?php echo htmlspecialchars($this->data['form']); ?>" method="post">
        <input type="hidden" name="stateId" value="<?php echo htmlspecialchars($this->data['stateId']) ?>"/>

        <label for="authorizationCodes">
            <?php echo $this->t('{oauth2server:oauth2server:status_authorization_code_header}'); ?>
        </label>
        <table id="authorizationCodes">
            <tr>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_id_header}'); ?></th>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_client_id_header}'); ?></th>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_expire_time_header}'); ?></th>
            </tr>

            <?php
            foreach ($this->data['authorizationCodes'] as $token) {
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($token['id']); ?></td>
                    <td><?php echo htmlspecialchars($token['clientId']); ?></td>
                    <td><?php echo htmlspecialchars(date("Y/m/d H:i:s", $token['expire'])); ?></td>
                </tr>
            <?php
            }
            ?>
        </table>

        <label for="refreshTokens">
            <?php echo $this->t('{oauth2server:oauth2server:status_refresh_token_header}'); ?>
        </label>
        <table id="refreshTokens">
            <tr>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_id_header}'); ?></th>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_client_id_header}'); ?></th>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_expire_time_header}'); ?></th>
            </tr>

            <?php
            foreach ($this->data['refreshTokens'] as $token) {
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($token['id']); ?></td>
                    <td><?php echo htmlspecialchars($token['clientId']); ?></td>
                    <td><?php echo htmlspecialchars(date("Y/m/d H:i:s", $token['expire'])); ?></td>
                </tr>
            <?php
            }
            ?>
        </table>

        <label for="accessTokens">
            <?php echo $this->t('{oauth2server:oauth2server:status_access_token_header}'); ?>
        </label>
        <table id="accessTokens">
            <tr>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_id_header}'); ?></th>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_client_id_header}'); ?></th>
                <th><?php echo $this->t('{oauth2server:oauth2server:status_token_expire_time_header}'); ?></th>
            </tr>

            <?php
            foreach ($this->data['accessTokens'] as $token) {
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($token['id']); ?></td>
                    <td><?php echo htmlspecialchars($token['clientId']); ?></td>
                    <td><?php echo htmlspecialchars(date("Y/m/d H:i:s", $token['expire'])); ?></td>
                </tr>
            <?php
            }
            ?>

        </table>
    </form>
<?php

$this->includeAtTemplateBase('includes/footer.php');
