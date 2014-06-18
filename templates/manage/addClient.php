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

$this->data['header'] = $this->t('{oauth2server:oauth2server:client_header}');

$this->includeAtTemplateBase('includes/header.php');
?>
    <form action="<?php echo htmlspecialchars($this->data['form']); ?>" method="post">
        <input name="language" type="text" hidden=""
               value="<?php echo htmlspecialchars($this->getLanguage()); ?>"/>

        <table>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_id}'); ?></td>
                <td><input type="text" name="clientId" readonly
                           value="<?php echo htmlspecialchars($this->data['id']); ?>"/></td>
            </tr>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_description}'); ?></td>
                <td><textarea name="clientDescription"><?php
                        echo htmlspecialchars($this->data['clientDescription']);
                        ?></textarea>
                </td>
            </tr>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_owner}'); ?></td>
                <td><input type="text" name="owner" readonly
                           value="<?php echo htmlspecialchars($this->data['owner']); ?>"/></td>
            </tr>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_password}'); ?></td>
                <td><input type="text" name="password"
                           value="<?php echo htmlspecialchars($this->data['password']); ?>"/></td>
            </tr>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_alternative_password}'); ?></td>
                <td><input type="text" name="alternativePassword"
                           value="<?php echo htmlspecialchars($this->data['alternativePassword']); ?>"/></td>
            </tr>
            <?php
            $header = true;
            foreach ($this->data['scopes'] as $scope => $checked) {
                ?>
                <tr>
                    <td><?php echo $header ? $this->t('{oauth2server:oauth2server:token_scope}') : ''; ?></td>
                    <td><input type="checkbox" name="availableScopes[]" value="<?php echo htmlspecialchars($scope); ?>"
                            <?php echo $checked ? 'checked="true"' : '' ?>/>
                        <?php echo $this->t('{oauth2server:oauth2server:' . $scope . '}') ?></td>
                </tr>
                <?php
                $header = false;
            }
            ?>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_uri}'); ?></td>
                <td><textarea name="uris"><?php
                        foreach ($this->data['uris'] as $uri) {
                            echo htmlentities($uri) . PHP_EOL;
                        }
                        ?></textarea>
                </td>
            </tr>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_expire}'); ?></td>
                <td><input type="text" name="expire"
                           value="<?php echo htmlspecialchars($this->data['expire']); ?>"/>
                </td>
            </tr>
        </table>

        <p>
            <input name="cancel" type="submit"
                   value="<?php echo $this->t('{oauth2server:oauth2server:add_client_cancel}'); ?>"/>
            <input name="create" type="submit"
                   value="<?php echo $this->t('{oauth2server:oauth2server:add_client_submit}'); ?>"/>
        </p>
    </form>
<?php

$this->includeAtTemplateBase('includes/footer.php');
