<?php

declare(strict_types=1);

use HealthCheck\Dto\ResolvedSetting;

/**
 * @var ResolvedSetting $header
 * @var ResolvedSetting $secret
 * @var string          $endpointUrl
 * @var string|null     $notice
 */
?>
<div class="wrap">
    <h1>Health Check — Réglages</h1>

    <?php if ($notice === 'header_saved') { ?>
        <div class="notice notice-success is-dismissible"><p>En-tête HTTP mis à jour.</p></div>
    <?php } elseif ($notice === 'secret_regenerated') { ?>
        <div class="notice notice-success is-dismissible"><p>Nouvelle clé secrète générée.</p></div>
    <?php } ?>

    <h2>Authentification</h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="health_check_save_header">
        <?php wp_nonce_field('health_check_save_header'); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="health_check_header">Nom de l'en-tête HTTP</label></th>
                <td>
                    <input type="text" id="health_check_header" name="health_check_header"
                           value="<?php echo esc_attr((string) $header->value); ?>" class="regular-text"
                           <?php disabled($header->isLocked); ?>>
                    <p class="description">
                        <?php if ($header->isLocked) { ?>
                            Défini via la constante <code>HEALTH_CHECK_HEADER</code> dans <code>wp-config.php</code> ; non modifiable ici.
                        <?php } else { ?>
                            Nom de l'en-tête HTTP utilisé pour transmettre la clé secrète (ex. <code>Authorization</code>, <code>X-Health-Token</code>).
                        <?php } ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button('Enregistrer', 'primary', 'submit', false, $header->isLocked ? ['disabled' => 'disabled'] : []); ?>
    </form>

    <hr>

    <h2>Clé secrète</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Clé actuelle</th>
            <td>
                <?php if ($secret->value !== null) { ?>
                    <input type="text" readonly class="regular-text" id="health_check_secret_display"
                           value="<?php echo esc_attr($secret->value); ?>">
                    <button type="button" class="button" data-copy-target="health_check_secret_display">Copier</button>
                <?php } else { ?>
                    <em>Aucune clé définie.</em>
                <?php } ?>
                <p class="description">
                    <?php if ($secret->isLocked) { ?>
                        Défini via la constante <code>HEALTH_CHECK_SECRET</code> dans <code>wp-config.php</code> ; non modifiable ici.
                    <?php } elseif ($secret->value === null) { ?>
                        Tant qu'aucune clé n'est définie, les résultats détaillés du health check ne sont jamais exposés.
                    <?php } ?>
                </p>
            </td>
        </tr>
    </table>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="health_check_regenerate_secret">
        <?php wp_nonce_field('health_check_regenerate_secret'); ?>
        <?php submit_button(
            $secret->value === null ? 'Générer une clé' : 'Régénérer',
            'secondary',
            'submit',
            false,
            $secret->isLocked ? ['disabled' => 'disabled'] : [],
        ); ?>
    </form>

    <hr>

    <h2>Point de terminaison</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><label for="health_check_endpoint_url">URL</label></th>
            <td>
                <input type="text" readonly class="regular-text" id="health_check_endpoint_url"
                       value="<?php echo esc_url($endpointUrl); ?>">
                <button type="button" class="button" data-copy-target="health_check_endpoint_url">Copier</button>
            </td>
        </tr>
    </table>
</div>

<script>
document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var el = document.getElementById(btn.getAttribute('data-copy-target'));
        if (!el || !navigator.clipboard) {
            return;
        }
        var original = btn.textContent;
        navigator.clipboard.writeText(el.value).then(function () {
            btn.textContent = 'Copié !';
            setTimeout(function () {
                btn.textContent = original;
            }, 1500);
        }, function () {
            btn.textContent = 'Échec';
            setTimeout(function () {
                btn.textContent = original;
            }, 1500);
        });
    });
});
</script>
