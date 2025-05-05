$(document).ready(function() {
    $(window).scroll(function() {
      var scroll = $(window).scrollTop();
      // Al hacer scroll >= 60 px
      if (scroll >= 60) {
        $(".navbar").addClass("bg-light");
      } else {
        $(".navbar").removeClass("bg-light");
      }
    });
  });
  
  document.addEventListener("DOMContentLoaded", function(){
    var nombreInput = document.getElementById('nombre');
    if(nombreInput){
        nombreInput.addEventListener('keydown', function(e) {
           if (e.key === 'Enter' || e.keyCode === 13) {
              e.preventDefault();
           }
        });
    }
  });
  