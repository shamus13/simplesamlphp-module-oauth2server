<?php

$this->data['header'] = $this->t('{oauth2server:oauth2server:consent_header}');

$this->includeAtTemplateBase('includes/header.php');
?>
    <form action="<?php echo htmlspecialchars($this->data['form']); ?>" method="post">
        <input type="hidden" name="stateId" value="<?php echo htmlspecialchars($this->data['stateId']) ?>"/>

        <table>
            <tr>
                <th><?php echo $this->t('oauth2server:oauth2server:consent_scope_name_header'); ?></th>
                <th><?php echo $this->t('oauth2server:oauth2server:consent_scope_grant_header'); ?></th>
            </tr>

            <?php
            foreach ($this->data['scopes'] as $scope) {
                echo('<tr><td>' . $this->t('oauth2server:oauth2server:' . $scope) .
                    '</td><td><input type="checkbox" name="grantedScopes[]" value="' . htmlspecialchars($scope) .
                    '"/></td></tr>');
            }
            ?>
        </table>

        <input id="deny" name="deny" type="submit"
               value="<?php echo $this->t('{oauth2server:oauth2server:deny_consent_description}'); ?>"/>
        <input id="grant" name="grant" type="submit"
               value="<?php echo $this->t('{oauth2server:oauth2server:grant_consent_description}'); ?>"/>
    </form>

<?php

$this->includeAtTemplateBase('includes/footer.php');
