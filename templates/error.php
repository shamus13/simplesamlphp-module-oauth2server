<?php

$this->data['header'] = $this->t('{oauth2server:oauth2server:authorization_error_header}');

$this->includeAtTemplateBase('includes/header.php');
?>
    <h2>
        <?php
        echo $this->data['error'];
        ?>
    </h2>

    <p>
        <?php
        echo $this->t('{oauth2server:oauth2server:authorization_error_' . $this->data['error'] . '}');
        ?>
    </p>

<?php

$this->includeAtTemplateBase('includes/footer.php');
