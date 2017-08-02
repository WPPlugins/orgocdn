console.log("Hello from interface");

renderGraphs();

function getImages(callback)
{
    if(typeof callback != 'function')
    {
        return false;
    }
    jQuery.post({url: ajaxurl, data: {action: 'get_images', verify: ajaxnonce} , success: function(response){
        callback(JSON.parse(response));
    }});
}
