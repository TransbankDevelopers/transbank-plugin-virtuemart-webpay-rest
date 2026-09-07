<?php
defined('JPATH_BASE') or exit();

if (!class_exists('vmPSPlugin')) {
    require_once JPATH_VM_PLUGINS . DS . 'vmpsplugin.php';
}

include_once dirname(dirname(__FILE__)) . '/library/HealthCheck.php';
include_once dirname(dirname(__FILE__)) . '/library/LogHandler.php';
include_once dirname(dirname(__FILE__)) . '/library/ConfigProvider.php';

$cid = vRequest::getvar('cid', null, 'array');
if (is_array($cid)) {
    $virtuemart_paymentmethod_id = $cid[0];
} else {
    $virtuemart_paymentmethod_id = $cid;
}

$virtuemart_paymentmethod_id = (int) $virtuemart_paymentmethod_id;
$baseUrl = "index.php?option=com_virtuemart&view=paymentmethod&task=edit&cid[]={$virtuemart_paymentmethod_id}";
$urlCheckTransaction = $baseUrl . '&checkTransaction=true';

$confProv = new ConfigProvider();
$configBd = $confProv->getConfig();

if (!isset($configBd['ambiente']) || trim($configBd['ambiente']) == '') {
    $configBd = $confProv->getConfigFromXml();
}

$config = [
    'MODO'          => $configBd['ambiente'],
    'COMMERCE_CODE' => $configBd['id_comercio'],
    'API_KEY'   => $configBd['api_key'],
    'ECOMMERCE'     => 'virtuemart',
];

$loghandler = new LogHandler();
$logs = json_decode($loghandler->getResume());

$healthCheck = new HealthCheck($config);
$res = json_decode($healthCheck->printFullResume());

function showOkOrError($status)
{
    if ($status == 'OK') {
        return "<span class='label label-success'>OK</span>";
    } else {
        return "<span class='label label-danger'>{$status}</span>";
    }
}

if (isset($logs->last_log->log_content)) {
    $res_logcontent = $logs->last_log->log_content;
    $log_file = $logs->last_log->log_file;
    $log_file_weight = $logs->last_log->log_weight;
    $log_file_regs = $logs->last_log->log_regs_lines;
} else {
    $res_logcontent = $logs->last_log;
    $log_file = json_encode($res_logcontent);
    $log_file_weight = $log_file;
    $log_file_regs = $log_file;
}

?>

<style media="screen">
    .no-border {}

    H3.menu-head {
        background-color: #d6012f;
        color: #ffffff;
    }

    .invisible {
        visibility: hidden;
    }

    .tbk_table_info {
        width: 100% !important;
        line-height: 18pt;
    }

    .tbk_table_td {
        width: 40%;
    }

    .tbk_table_trans {
        width: 60%;
    }

    .modal-tbk {
        overflow-y: auto;
        max-height: 90vh;
    }

    .tbk-response-container {
        display: grid;
        grid-template-columns: 20px 300px 1fr;
        grid-gap: 5px;
        align-items: flex-start;
        overflow: hidden;
    }

    .info-column {
        padding-top: 5px;
        padding-bottom: 5px;
        text-align: left;
        word-wrap: break-word;
    }

    .highlight-text {
        font-weight: bold;
    }

    .label.label-info {
        padding: 5px;
        float: left;
        margin-right: 5px;
        background: #666;
        border-radius: 7px;
        width: 7px;
        height: 7px;
        color: #fff;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .info-value {
        text-align: left;
    }

    .label-success {
        background: #5cb85c;
        border-radius: 5px;
        padding: 5px;
        color: #fff;
        font-weight: bold;
        font-size: 10px;
    }

    .label-danger {
        background: #ec3206;
        border-radius: 5px;
        padding: 5px;
        color: #fff;
        font-weight: bold;
        font-size: 10px;
    }
</style>

<div class="modal fade modal-tbk" id="tb_commerce_mod_info" tabindex="-1" role="dialog"
    aria-labelledby="" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tb_main_info" data-toggle="tab">Información</a></li>
                        <li><a href="#tb_logs" data-toggle="tab">Registros</a></li>
                    </ul>
                </h4>
            </div>
            <!--Inicio modal-body-->
            <div class="modal-body">
                <!--Inicio main info-->
                <div id="tb_main_info" class="tab-pane fade active in">
                    <!-- inicio container-fluid -->
                    <div class="container-fluid">
                        <div class="no-border">
                            <h3 class="menu-head">Informacion de Plugin / Ambiente</h3>
                            <div class="tbk-response-container" id="div_plugin-info">
                                <div class="info-column">
                                    <div title="Nombre del E-commerce instalado en el servidor"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Software E-commerce </span>
                                </div>
                                <div class="info-column">
                                    <span class="info-value">
                                        <?php echo $res->server_resume->plugin_info->ecommerce; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="tbk-response-container" id="div_version_plugin">
                                <div class="info-column">
                                    <div title="Versión del e-commerce instalado en el servidor"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Version E-commerce</span>
                                </div>
                                <div class="info-column">
                                    <span class="info-value">
                                        <?php echo $res->server_resume->plugin_info->ecommerce_version; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="tbk-response-container" id="div_version_webpay_plugin">
                                <div class="info-column">
                                    <div title="Versión del plugin Webpay instalada actualmente"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Version Plugin Webpay Instalada</span>
                                </div>
                                <div class="info-column">
                                    <span class="info-value">
                                        <?php echo $res->server_resume->plugin_info->current_plugin_version; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="tbk-response-container" id="div_last_version_webpay">
                                <div class="info-column">
                                    <div title="Última versión del plugin Webpay disponible"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Última Versión de Plugin Disponible</span>
                                </div>
                                <div class="info-column">
                                    <span class="info-value">
                                        <?php echo $res->server_resume->plugin_info->last_plugin_version; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="no-border">
                            <h3 class="menu-head">Información de Servidor</h3>
                            <h4>Informacion Principal</h4>
                            <div class="tbk-response-container" id="div_web_server_info">
                                <div class="info-column">
                                    <div title="Descripción del Servidor Web instalado"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Software Servidor</span>
                                </div>
                                <div class="info-column">
                                    <span class="info-value">
                                        <?php echo $res->server_resume->server_version->server_software; ?>
                                    </span>
                                </div>
                            </div>
                            <h4>PHP</h4>
                            <div class="tbk-response-container" id="div_php_status_webpay">
                                <div class="info-column">
                                    <div title="Informa si la versión de PHP instalada en el servidor es compatible con el plugin de Webpay"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Estado</span>
                                </div>
                                <div class="info-column">
                                    <span class="info-value">
                                        <?php echo showOkOrError($res->server_resume->php_version->status); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="tbk-response-container" id="div_php_info">
                                <div class="info-column">
                                    <div title="Versión de PHP instalada en el servidor"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Versión</span>
                                </div>
                                <div class="info-column">
                                    <span class="info-value">
                                        <?php echo $res->server_resume->php_version->version; ?>
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <h4 id="php_req_extensions">Extensiones PHP requeridas</h4>
                            <table aria-describedby="php_req_extensions" class="table table-responsive table-striped">
                                <thead>
                                    <th>Extension</th>
                                    <th>Estado</th>
                                    <th>Version</th>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><b>openssl</b></td>
                                        <td>
                                            <?php echo showOkOrError($res->php_extensions_status->openssl->status); ?>
                                        </td>
                                        <td>
                                            <?php echo $res->php_extensions_status->openssl->version; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><b>SimpleXML</b></td>
                                        <td>
                                            <?php echo showOkOrError($res->php_extensions_status->SimpleXML->status); ?>
                                        </td>
                                        <td>
                                            <?php echo $res->php_extensions_status->SimpleXML->version; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><b>dom</b></td>
                                        <td>
                                            <?php echo showOkOrError($res->php_extensions_status->dom->status); ?>
                                        </td>
                                        <td>
                                            <?php echo $res->php_extensions_status->dom->version; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="no-border">
                            <h3 class="menu-head">Validación Transacción</h3>
                            <h4>Petición a Transbank</h4>
                            <div>
                                <button id="btn-check-transaction" class="btn btn-sm btn-primary">Verificar Conexión</button>
                            </div>
                            <h4>Respuesta de Transbank</h4>
                            <div class="tbk-status" id="div_status_tbk">
                                <div class="tbk-response-container" id="div_status">
                                    <div class="info-column">
                                        <div title="Estado de comunicación con Transbank mediante create_transaction"
                                            class="label label-info">?
                                        </div>
                                    </div>
                                    <div class="info-column">
                                        <span class="highlight-text"> Estado: </span>
                                    </div>
                                    <div class="info-column">
                                        <span id="response_status_text"></span>
                                    </div>
                                </div>
                                <div class="tbk-response-container" id="div_response_url">
                                    <div class="info-column">
                                        <div title="URL entregada por Transbank para realizar la transacción"
                                            class="label label-info">?
                                        </div>
                                    </div>
                                    <div class="info-column">
                                        <span class="highlight-text"> URL: </span>
                                    </div>
                                    <div class="info-column" id="response_url_text">
                                    </div>
                                </div>
                                <div class="tbk-response-container" id="div_response_token">
                                    <div class="info-column">
                                        <div title="Token entregada por Transbank para realizar la transacción"
                                            class="label label-info">?
                                        </div>
                                    </div>
                                    <div class="info-column">
                                        <span class="highlight-text"> Token: </span>
                                    </div>
                                    <div class="info-column">
                                        <code id="response_token_text"></code>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--fin container-fluid -->
                    </div>
                    <!-- fin main info -->
                </div>
                <div class="tab-pane fade" id="tb_logs">
                    <div class="container-fluid">
                        <div id="maininfo">
                            <h3 class="menu-head">Información de Registros</h3>
                            <div class="tbk-response-container" id="div_logs_path">
                                <div class="info-column">
                                    <div title="Carpeta que almacena logs con información de transacciones Webpay"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Directorio de Registros: </span>
                                </div>
                                <div class="info-column" id="log-status">
                                    <span>
                                        <?php echo htmlspecialchars(json_encode($logs->log_dir), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="tbk-response-container" id="div_numbers_of_file">
                                <div class="info-column">
                                    <div title="Cantidad de archivos que guardan información de transacciones Webpay"
                                        class="label label-info">?
                                    </div>
                                </div>
                                <div class="info-column">
                                    <span class="highlight-text">Cantidad de Registros en Directorio: </span>
                                </div>
                                <div class="info-column" id="log-status">
                                    <span>
                                        <?php echo json_encode($logs->logs_count->log_count); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <h3 class="menu-head">Ultimos Registros</h3>
                        <div class="tbk-response-container" id="div_last_log">
                            <div class="info-column">
                                <div title="Nombre del útimo archivo de registro creado"
                                    class="label label-info">?
                                </div>
                            </div>
                            <div class="info-column">
                                <span class="highlight-text">Último Documento: </span>
                            </div>
                            <div class="info-column">
                                <span>
                                    <?php echo $log_file; ?>
                                </span>
                            </div>
                        </div>
                        <div class="tbk-response-container" id="div_last_log_size">
                            <div class="info-column">
                                <div title="Peso del último archivo de registro creado"
                                    class="label label-info">?
                                </div>
                            </div>
                            <div class="info-column">
                                <span class="highlight-text">Peso de Documento: </span>
                            </div>
                            <div class="info-column">
                                <span>
                                    <?php echo $log_file_weight; ?>
                                </span>
                            </div>
                        </div>
                        <div class="tbk-response-container" id="div_log_file_regs">
                            <div class="info-column">
                                <div title="Cantidad de líneas que posee el último archivo de registro creado"
                                    class="label label-info">?
                                </div>
                            </div>
                            <div class="info-column">
                                <span class="highlight-text">Cantidad de Líneas: </span>
                            </div>
                            <div class="info-column">
                                <span>
                                    <?php echo $log_file_regs; ?>
                                </span>
                            </div>
                        </div>
                        <br>
                        <b>Contenido último Log: </b>
                        <div class="log_content">
                            <pre>
                                <code><?php echo htmlspecialchars((string) $res_logcontent, ENT_QUOTES, 'UTF-8'); ?></code>
                            </pre>
                        </div>
                    </div>
                </div>
                <!--FIN modalbody-->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery().ready(function($) {

        $('#tb_commerce_mod_info').hide();

        $('#tb_commerce_mod_info').on('show.bs.modal', function() {
            $('.modal .modal-body').css('overflow-y', 'auto');
            $('.modal .modal-body').css('max-height', $(window).height() * 0.6);
            $('.modal .modal-body').css('min-height', $(window).height() * 0.6);
        });

        $('#tb_logs').hide();

        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var target = $(e.target).attr("href");
            if (target == '#tb_main_info') {
                $('#tb_main_info').show();
                $('#tb_logs').hide();
            } else {

                $('#tb_main_info').hide();
                $('#tb_logs').show();
            }
        });

        $('#btn-check-transaction').click(function(evt) {
            var el = $(this);
            el.text('Verificar conexión...');
            $.getJSON(<?php echo json_encode($urlCheckTransaction); ?>, function(resp) {
                el.text('Verificar conexión');
                var status = '';
                if (resp.status.string == 'OK') {
                    status = "<span class='label label-success'>OK</span>";
                } else {
                    status = "<span class='label label-danger'>Error</span>";
                }
                $('#response_status_text').empty().append(status);
                $('#response_url_text').text(resp.response.url || resp.response.error);
                $('#response_token_text').text(resp.response.token_ws || resp.response.detail);
            });
            evt.preventDefault();
        });
    })
</script>
