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

$this->data['header'] = $this->t('{oauth2server:oauth2server:consent_header}');

$this->includeAtTemplateBase('includes/header.php');
?>
    <form action="<?php echo htmlspecialchars($this->data['form']); ?>" method="post">
        <input type="hidden" name="stateId" value="<?php echo htmlspecialchars($this->data['stateId']) ?>"/>

        <h3>
            <?php echo $this->t('{oauth2server:oauth2server:consent_client_id}'); ?>
            "<?php echo htmlentities($this->data['clientId']); ?>"
        </h3>

        <p>
            <?php echo $this->t('{oauth2server:oauth2server:client_description}'); ?>
        </p>

        <h3>
            <?php echo $this->t('{oauth2server:oauth2server:consent_scope_header}'); ?>
        </h3>
        <table>
            <tr>
                <th><?php echo $this->t('{oauth2server:oauth2server:consent_scope_name}'); ?></th>
                <th><?php echo $this->t('{oauth2server:oauth2server:consent_scope_grant}'); ?></th>
            </tr>

            <?php
            foreach ($this->data['scopes'] as $scope) {
                ?>
                <tr>
                    <td><?php echo $this->t('{oauth2server:oauth2server:' . $scope . '}') ?></td>
                    <td>
                        <input type="checkbox" name="grantedScopes[]" value="<?php echo htmlspecialchars($scope); ?>"/>
                    </td>
                </tr>
            <?php
            }
            ?>
        </table>

        <p>
            <label for="ttl">
                <?php echo $this->t('{oauth2server:oauth2server:consent_refresh_token_ttl}'); ?>
            </label>
            <select name="ttl" id="ttl">
                <?php foreach ($this->data['ttlChoices'] as $ttl) { ?>
                    <option value="<?php echo htmlentities($ttl) ?>"
                        <?php echo $this->data['ttlDefault'] == $ttl ? 'selected="selected"' : '' ?>>
                        <?php echo htmlentities($ttl) /*TODO: we need to add labels instead of using seconds */?>
                    </option>
                <?php } ?>
            </select>
        </p>

        <p>
            <input id="deny" name="deny" type="submit"
                   value="<?php echo $this->t('{oauth2server:oauth2server:consent_deny_description}'); ?>"/>
            <input id="grant" name="grant" type="submit"
                   value="<?php echo $this->t('{oauth2server:oauth2server:consent_grant_description}'); ?>"/>
        </p>
    </form>

<?php

$this->includeAtTemplateBase('includes/footer.php');
