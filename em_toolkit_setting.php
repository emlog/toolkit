<?php

if (!defined('EMLOG_ROOT')) {
    die('err');
}

if (!class_exists('EmToolKit', false)) {
    include __DIR__ . '/em_toolkit.php';
}

function plugin_setting_view() {

    ?>
    <?php if (isset($_GET['suc'])): ?>
        <div class="alert alert-success">保存成功</div>
    <?php endif; ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">小工具</h1>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4 mt-2">
                <h6 class="card-header">更换域名</h6>
                <div class="card-body" id="admindex_msg">
                    <div class="form-group form-check">
                        <div class="alert alert-info">将替换所有文章中的原站点地址为新的地址（包括图片链接等）</div>
                        <div class="form-group">
                            <label>原站点地址（格式如： https://www.emlog.net）</label>
                            <input type="url" class="form-control" value="<?= BLOG_URL ?>" name="olddomain" id="olddomain" required>
                        </div>
                        <div class="form-group">
                            <label>新的站点地址</label>
                            <input type="url" class="form-control" value="" name="newdomain" id="newdomain">
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button id="changeDomainBtn" class="btn btn-sm btn-success">开始更换</button>
                            <div id="statusMsg" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4 mt-2">
                <h6 class="card-header">RSS数据导入</h6>
                <div class="card-body" id="admindex_msg">
                    <div class="form-group form-check">
                        <div class="alert alert-info">从其他类型站点的 RSS 导入数据，目前支持 WordPress 和 Typecho</div>
                        <div class="form-group">
                            <label>RSS地址（如： https://typecho.org/feed/）</label>
                            <input type="url" class="form-control" value="" name="rss_url" id="rss_url" required>
                        </div>
                        <div class="form-group form-inline">
                            博客类型：<select name="blogtype" class="form-control" style="width: 120px;">
                                <option value="wordpress">wordpress</option>
                                <option value="typecho">Typecho</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button id="importRssBtn" class="btn btn-sm btn-success">开始导入</button>
                            <div id="importRssStatusMsg" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4 mt-2">
                <h6 class="card-header">数据表修复</h6>
                <div class="card-body" id="admindex_msg">
                    <div class="form-group form-check">
                        <div class="alert alert-primary">
                            如果你的站点出现SQL错误提示，内容有"try to repair it"字样，说明数据库表需要修复。<br/>
                            本功能可以一键修复emlog数据库中所有出错的数据表。
                        </div>
                        <button id="repairTableBtn" class="btn btn-sm btn-success">开始修复</button>
                        <div id="repairTableStatusMsg" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        setTimeout(hideActived, 3600);
        $("#menu_category_ext").addClass('active');
        $("#menu_ext").addClass('show');
        $("#menu_plug_em_toolkit").addClass('active');
    </script>
    <script>
        $(document).ready(function () {
            $('#changeDomainBtn').click(function (e) {
                e.preventDefault();
                var oldDomain = $('#olddomain').val();
                var newDomain = $('#newdomain').val();

                $('#statusMsg').html('<div class="spinner-border text-success" role="status"><span class="sr-only">Loading...</span></div>');

                $.ajax({
                    url: './plugin.php?plugin=em_toolkit',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        tool_action: 'change_domain',
                        olddomain: oldDomain,
                        newdomain: newDomain
                    },
                    success: function (data) {
                        if (data.code === 0) {
                            $('#statusMsg').html('<div class="text-success">' + data.data + '</div>');
                        } else {
                            $('#statusMsg').html('<div class="text-danger">' + data.data + '</div>');
                        }
                    },
                    error: function (xhr, status, error) {
                        if (xhr.responseText) {
                            var responseJson = JSON.parse(xhr.responseText);
                            $('#statusMsg').html('<div class="text-danger">' + responseJson.msg + '</div>');
                        } else {
                            $('#statusMsg').html('<div class="text-danger">' + error + '</div>');
                        }
                    }
                });
            });
            $('#repairTableBtn').click(function (e) {
                e.preventDefault();

                $('#repairTableStatusMsg').html('<div class="spinner-border text-success" role="status"><span class="sr-only">Loading...</span></div>');

                $.ajax({
                    url: './plugin.php?plugin=em_toolkit',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        tool_action: 'repair_table'
                    },
                    success: function (data) {
                        if (data.code === 0) {
                            $('#repairTableStatusMsg').html('<div class="text-success">' + data.data + '</div>');
                        } else {
                            $('#repairTableStatusMsg').html('<div class="text-danger">' + data.data + '</div>');
                        }
                    },
                    error: function (xhr, status, error) {
                        if (xhr.responseText) {
                            var responseJson = JSON.parse(xhr.responseText);
                            $('#repairTableStatusMsg').html('<div class="text-danger">' + responseJson.msg + '</div>');
                        } else {
                            $('#repairTableStatusMsg').html('<div class="text-danger">' + error + '</div>');
                        }
                    }
                });
            });
            $('#importRssBtn').click(function (e) {
                e.preventDefault();
                var rssUrl = $('#rss_url').val();
                var blogType = $('#blogtype').val();

                $('#importRssStatusMsg').html('<div class="spinner-border text-success" role="status"><span class="sr-only">Loading...</span></div>');

                $.ajax({
                    url: './plugin.php?plugin=em_toolkit',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        tool_action: 'import_rss',
                        rssurl: rssUrl,
                        blogtype: blogType
                    },
                    success: function (data) {
                        if (data.code === 0) {
                            $('#importRssStatusMsg').html('<div class="text-success">' + data.data + '</div>');
                        } else {
                            $('#importRssStatusMsg').html('<div class="text-danger">' + data.data + '</div>');
                        }
                    },
                    error: function (xhr, status, error) {
                        if (xhr.responseText) {
                            var responseJson = JSON.parse(xhr.responseText);
                            $('#importRssStatusMsg').html('<div class="text-danger">' + responseJson.msg + '</div>');
                        } else {
                            $('#importRssStatusMsg').html('<div class="text-danger">' + error + '</div>');
                        }
                    }
                });
            });
        });
    </script>
<?php }

$toolAction = Input::postStrVar('tool_action');

if ($toolAction === 'change_domain') {
    $oldDomain = Input::postStrVar('olddomain');
    $newDomain = Input::postStrVar('newdomain');

    $toolKit = EmToolKit::getInstance();

    $toolKit->changeDomain($oldDomain, $newDomain);
}

if ($toolAction === 'repair_table') {
    $toolKit = EmToolKit::getInstance();
    $toolKit->repairTables();
}

if ($toolAction === 'import_rss') {
    $rssUrl = Input::postStrVar('rssurl');
    $blogType = Input::postStrVar('blogtype');

    $toolKit = EmToolKit::getInstance();

    $toolKit->rssImport($rssUrl, $blogType);
}
