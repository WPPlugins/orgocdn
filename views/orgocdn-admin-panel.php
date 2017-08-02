<script>var ajaxnonce = '<?php echo $nonce ?>' </script>

    <div class="container" style="float: left !important; width: 100%; max-width: 1000px;">
        <h3>Orgo Tech Image Optimization</h3>
        <div id="noimages" class="alert alert-warning" role="alert" style="display: none">No images need optimizing.</div>
        <div class="row">
            <?php if(get_option('orgotech_background_running') == 1): ?>
                <div id="status" class="col-md-12">
                    <div class="alert alert-info" role="alert">Currently optimizing...</div>
                </div>
            <?php elseif(get_option('orgotech_background_running') == 2): ?>
                <div id="status" class="col-md-12">
                    <div class="alert alert-success" role="alert">Done optimizing!</div>
                </div>
            <?php endif; ?>
            <div class="col-md-12"><br /></div>
            <div class="col-md-7">

                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="col-md-12"><br /></div>
                        <div class="col-xs-6">
                            <?php if(get_option('orgotech_background_running') == 1): ?>
                                <button type="submit" id="submit" class="btn btn-primary" function="stop">Stop</button>
                            <?php elseif(get_option('orgotech_background_running') == 0): ?>
                                <button type="submit" id="submit" class="btn btn-primary" function="start">Start Optimizing</button>
                            <?php elseif(get_option('orgotech_background_running') == 2): ?>
                                <button type="submit" id="submit" class="btn btn-success" function="done">Done</button>
                            <?php endif; ?>
                        </div>
                        <div class="col-xs-12">
                            <hr />
                            <?php if(get_option('orgotech_background_running') == 1): ?>
                                <div class="progress" style="display: none;">
                                    <div class="progress-bar progress-bar-striped active">

                                    </div>
                                </div>
                            <?php elseif(get_option('orgotech_background_running') == 2): ?>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped active progress-bar-success" style="width: 100%">

                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="progress" style="display: none;">
                                    <div class="progress-bar progress-bar-striped active progress-bar" style="width: 100%">

                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <table class="table table-striped">
                            <tr>
                                <td>Images</td>
                                <td id="images"><?php echo $api->images ?></td>
                            </tr>
                            <tr>
                                <td>Removed %</td>
                                <td id="removedPercent"><?php echo number_format(round($api->stop_size ? 100 - 100 * $api->stop_size / $api->start_size : 0, 2), 2) ?> %</td>
                            </tr>
                            <tr>
                                <td>Removed Data:</td>
                                <td id="removedData"><?php $number = $this->get_prefixed($api->start_size - $api->stop_size); echo number_format(round($number['number'], 2), 2) . $number['prefix']  ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function updateNumbers(api)
{
    api = JSON.parse(api['data']);
    jQuery("#images").html(api['images']);
    if(api['stop_size'] > 0)
    {
        var percent =  (100 - 100 * api['stop_size'] / api['start_size']).toFixed(2);
    }
    else
    {
        var percent =  0;
    }

    var data = api['start_size'] - api['stop_size'];
    var prefix;
    var i = 0;
    while(data > 1024)
    {
        data = data / 1024;
        i+=1;
    }

    switch(i)
    {
        case 0: prefix = "B"; break;
        case 1: prefix = "KB"; break;
        case 2: prefix = "MB"; break;
        case 3: prefix = "GB"; break;
        case 4: prefix = "TB"; break;
    }

    jQuery("#removedPercent").html(percent + " %");
    jQuery("#removedData").html(data.toFixed(2) + " " + prefix);
}

function getAPI(callback)
{
    if(typeof callback != 'function')
    {
        return false;
    }

    jQuery.post({url: ajaxurl, data: {action: 'get_api', verify: ajaxnonce} , success: function(response){
        callback(JSON.parse(response)['data']);
        setTimeout(function() {getAPI(callback)}, 3000);
    }});
}

function startOptimize()
{
    jQuery.post({url: ajaxurl, data: {'verify' : ajaxnonce, 'action' : 'run_optimizer'}, success: function(){

    }});
}

function stopOptimize()
{
    jQuery.post({url: ajaxurl, data: {'verify' : ajaxnonce, 'action' : 'stop_optimizer'}, success: function(){
    }});
}

function updateButton()
{
    jQuery.post({url: ajaxurl, data: {action: 'get_status', verify: ajaxnonce}, success: function(response)
    {
        var status = JSON.parse(response);
        if(status == 1)
        {
            jQuery("#submit").html("Stop");
            jQuery("#submit").attr("function", "stop");
            jQuery(".progress").css("display", "block");
            jQuery("#submit").removeClass("btn-success");
            jQuery("#submit").addClass("btn-primary");
        }
        else if(status == 0)
        {
            jQuery("#submit").html("Optimize now");
            jQuery("#submit").attr("function", "start");
            jQuery(".progress").css("display", "none");
            jQuery("#submit").removeClass("btn-success");
            jQuery("#submit").addClass("btn-primary");
        }
        else if(status == 2)
        {
            jQuery("#submit").html("Done");
            jQuery("#submit").attr("function", "done");
            jQuery(".progress").css("display", "block");
            jQuery("#submit").removeClass("btn-primary");
            jQuery("#submit").addClass("btn-success");
            setTimeout(stopOptimize, 1000);
        }
    }})
}

function getProgress()
{

    jQuery.post({url: ajaxurl, data: {action: 'get_progress', verify: ajaxnonce}, success: function(response)
    {
        var isRunning = JSON.parse(response);
        switch(isRunning){
            case '0': button_stopped(); break;
            case '1': button_running(); break;
            case '2': button_done(); break;
        }

        setTimeout(getProgress, 3000);
    }});
}

function getProgressNumbers()
{
    jQuery.post({url: ajaxurl, data: {action: 'get_progress_numbers', verify: ajaxnonce}, success: function(response)
    {
        var numbers = JSON.parse(response);

        if(numbers['total'] > 0)
        {
            var percent = Math.floor(100 * numbers['current'] / numbers['total']);
            var percent_string = percent + "%";

            jQuery(".progress-bar").css("width", percent_string);
            jQuery(".progress-bar").html(percent_string);
            jQuery(".progress").css("display", "block");
        }
        else
        {
            stopOptimize();
            jQuery(".progress").css("display", "none");
        }

        setTimeout(getProgressNumbers, 3000);
    }});
}

function button_stopped()
{
    jQuery("#submit").html("Start Optimizing");
    jQuery("#submit").attr("function", "stopped");
    jQuery("#submit").removeClass("btn-success");
    jQuery("#submit").addClass("btn-primary");
}

function button_running()
{
    jQuery("#submit").html("Stop");
    jQuery("#submit").attr("function", "running");
    jQuery("#submit").removeClass("btn-success");
    jQuery("#submit").addClass("btn-primary");
}

function button_done()
{
    jQuery("#submit").html("Done");
    jQuery("#submit").attr("function", "done");
    jQuery("#submit").removeClass("btn-primary");
    jQuery("#submit").addClass("btn-success");

}

function optimize_run()
{
    jQuery.post({url: ajaxurl, data: {'verify' : ajaxnonce, 'action' : 'run_optimizer'}, success: function(response){


        if(!JSON.parse(response))
        {
            jQuery("#noimages").css("display", "block")
        }
        else
        {
            jQuery("#noimages").css("display", "none");
            getProgress("");
        }
    }});
}

function optimize_stop()
{
    jQuery.post({url: ajaxurl, data: {'verify' : ajaxnonce, 'action' : 'stop_optimizer'}, success: function(){
        getProgress("");
    }});
}

function optimize_done()
{
    jQuery.post({url: ajaxurl, data: {'verify' : ajaxnonce, 'action' : 'done_optimizer'}, success: function(){
        getProgress("");
    }});
}

function button()
{
    switch(jQuery("#submit").attr("function"))
    {
        case 'stopped': optimize_run(); break;
        case 'running': optimize_stop(); break;
        case 'done': optimize_done(); break;
    }
}

setTimeout(function() {jQuery("#status").css("display", "none")}, 3000);

getProgressNumbers();
getProgress();


getAPI(updateNumbers);

jQuery("#submit").on("click", button);

</script>
