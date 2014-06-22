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

if (isset($this->data['id'])) {
    ?>
    <table>
        <tr>
            <td><?php echo $this->t('{oauth2server:oauth2server:client_id}'); ?></td>
            <td><?php echo htmlspecialchars($this->data['id']); ?></td>
        </tr>
        <tr>
            <td><?php echo $this->t('{oauth2server:oauth2server:client_description}'); ?></td>
            <td><?php echo $this->t('{oauth2server:oauth2server:client_description_text}'); ?></td>
        </tr>
        <?php
        if (isset($this->data['owner'])) {
            ?>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_owner}'); ?></td>
                <td><?php echo htmlspecialchars($this->data['owner']); ?></td>
            </tr>
        <?php
        }
        ?>
        <?php
        if (isset($this->data['password'])) {
            ?>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_password}'); ?></td>
                <td><?php echo htmlspecialchars($this->data['password']); ?></td>
            </tr>
        <?php
        }
        ?>
        <?php
        if (isset($this->data['alternative_password'])) {
            ?>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_alternative_password}'); ?></td>
                <td><?php echo htmlspecialchars($this->data['alternative_password']); ?></td>
            </tr>
        <?php
        }
        ?>
        <?php
        $header = true;
        foreach ($this->data['scopes'] as $scope) {
            ?>
            <tr>
                <td><?php echo $header ? $this->t('{oauth2server:oauth2server:token_scope}') : ''; ?></td>
                <td><?php echo $this->t('{oauth2server:oauth2server:' . $scope . '}') ?></td>
            </tr>
            <?php
            $header = false;
        }
        ?>
        <?php
        if (isset($this->data['uris'])) {
            $header = true;
            foreach ($this->data['uris'] as $uri) {
                ?>
                <tr>
                    <td><?php echo $header ? $this->t('{oauth2server:oauth2server:client_uri}') : ''; ?></td>
                    <td><?php echo htmlentities($uri) ?></td>
                </tr>
                <?php
                $header = false;
            }
        }
        ?>
        <?php
        if (isset($this->data['expire'])) {
            ?>
            <tr>
                <td><?php echo $this->t('{oauth2server:oauth2server:client_expire}'); ?></td>
                <td><?php echo htmlspecialchars(date("Y/m/d H:i:s", $this->data['expire'])); ?></td>
            </tr>
        <?php
        }
        ?>
    </table>
<?php
}
?>

    <form name="back" action="<?php echo htmlspecialchars($this->data['backform']); ?>" method="GET">
        <input name="back" type="submit" value="<?php echo $this->t('{oauth2server:oauth2server:client_back}'); ?>"/>
    </form>

<?php
if (isset($this->data['editform'])) {
    ?>
    <form name="edit" action="<?php echo htmlspecialchars($this->data['editform']); ?>" method="GET">
        <button type="submit" name="clientId" value="<?php echo htmlentities($this->data['id']) ?>">
            <?php echo $this->t('{oauth2server:oauth2server:client_edit}'); ?>
        </button>
    </form>
<?php
}
?>

<?php
$this->includeAtTemplateBase('includes/footer.php');
