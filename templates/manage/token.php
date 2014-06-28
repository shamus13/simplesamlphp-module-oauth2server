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

$this->data['header'] = $this->t('{oauth2server:oauth2server:token_header}');

$this->includeAtTemplateBase('includes/header.php');
?>
    <form action="<?php echo htmlspecialchars($this->data['form']); ?>" method="post">
        <?php
        if (isset($this->data['token'])) {
            $token = $this->data['token'];

            ?>
            <input type="text" name="tokenId" hidden="hidden" readonly
                   value="<?php echo htmlspecialchars($token['id']); ?>"/>
            <table>
                <tr>
                    <td><?php echo $this->t('{oauth2server:oauth2server:token_id}'); ?></td>
                    <td><?php echo htmlspecialchars($token['id']); ?></td>
                </tr>
                <tr>
                    <td><?php echo $this->t('{oauth2server:oauth2server:token_type}'); ?></td>
                    <td><?php echo htmlspecialchars($token['type']); ?></td>
                </tr>
                <tr>
                    <td><?php echo $this->t('{oauth2server:oauth2server:client_id}'); ?></td>
                    <td><?php echo htmlspecialchars($token['clientId']); ?></td>
                </tr>
                <tr>
                    <td><?php echo $this->t('{oauth2server:oauth2server:client_description}'); ?></td>
                    <td><?php echo $this->t('{oauth2server:oauth2server:client_description_text}'); ?></td>
                </tr>
                <?php
                $header = true;
                foreach ($token['scopes'] as $scope) {
                    ?>
                    <tr>
                        <td><?php echo $header ? $this->t('{oauth2server:oauth2server:token_scope}') : ''; ?></td>
                        <td><?php echo $this->t('{oauth2server:oauth2server:' . $scope . '}') ?></td>
                    </tr>
                    <?php
                    $header = false;
                }
                ?>
                <tr>
                    <td><?php echo $this->t('{oauth2server:oauth2server:token_expire_time}'); ?></td>
                    <td><?php echo htmlspecialchars(date("Y/m/d H:i:s", $token['expire'])); ?></td>
                </tr>
            </table>
            <p>

                <input name="back" type="submit"
                       value="<?php echo $this->t('{oauth2server:oauth2server:token_back}'); ?>"/>
                <input name="revoke" type="submit"
                       value="<?php echo $this->t('{oauth2server:oauth2server:token_revoke}'); ?>"/>
            </p>
        <?php
        }
        ?>
    </form>
<?php

$this->includeAtTemplateBase('includes/footer.php');
