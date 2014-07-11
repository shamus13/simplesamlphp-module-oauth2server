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

$this->data['header'] = $this->t('{oauth2server:errors:authorization_error_short_' . $this->data['code'] . '}');

$this->includeAtTemplateBase('includes/header.php');
?>
    <h2>
        <?php
        echo $this->t('{oauth2server:errors:authorization_error_short_' . $this->data['code'] . '}');
        ?>
    </h2>

    <p>
        <?php
        echo $this->t('{oauth2server:errors:authorization_error_long_' . $this->data['code'] . '}',
            $this->data['parameters']);
        ?>
    </p>

<?php

$this->includeAtTemplateBase('includes/footer.php');
