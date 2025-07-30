<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/../wp-content/plugins/sog_explore_sql')
    ->in(__DIR__ . '/../wp-content/plugins/sog_settings')
    ->in(__DIR__ . '/../wp-content/plugins/sog-api')
    ->in(__DIR__ . '/../wp-content/plugins/sog-attachment-redirect')
    ->in(__DIR__ . '/../wp-content/plugins/sog-saml-conf')
    ->in(__DIR__ . '/../wp-content/plugins/sog-user-import')
    ->in(__DIR__ . '/../wp-content/plugins/unc-cookie-banner')
    ->in(__DIR__ . '/../wp-content/plugins/unc-utility-bar')
    ->in(__DIR__ . '/../wp-content/themes/sog-foundation-parent')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@PHP82Migration' => true,
    ])
    ->setFinder($finder)
;