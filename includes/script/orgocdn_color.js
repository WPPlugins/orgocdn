function setColor(images)
{
        if(images <= 100){
          var color;
          var frac = images / 100;
          var rgb = "rgb(0,255,0)";

          if(frac<=0.5)
          {
            rgb = "rgb("+Math.round(frac*510)+", 255, 0)";
          }
          else
          {
            rgb = "rgb(255,"+Math.round((1-frac)*510)+", 0)";
          }
          jQuery("#bar").css("background-color", rgb);
        }
    });
}

jQuery("body").ready(function(){
  setColor();
  jQuery("#bar").css("width", 100 - images + "%");
})
