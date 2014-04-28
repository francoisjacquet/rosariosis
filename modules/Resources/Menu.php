<?php
$menu['Resources']['admin'] = array(
            			'Resources/Resources.php'=>_('Resources'),
					);

$menu['Resources']['teacher'] = array(
                        'Resources/Resources.php'=>_('Resources'),
                    );
$menu['Resources']['parent'] = $menu['Resources']['teacher'];
$menu['Resources']['student'] = $menu['Resources']['parent'];
?>
