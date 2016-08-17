var al_isOpera = (navigator.userAgent.indexOf('Opera') != -1);
var al_isIE = (!al_isOpera && navigator.userAgent.indexOf('MSIE') != -1);
var al_isNav = (navigator.appName.indexOf("Netscape") !=-1);

window.Object.defineProperty( Element.prototype, 'documentOffsetTop', {
    get: function () {
        return this.offsetTop + ( this.offsetParent ? this.offsetParent.documentOffsetTop : 0 );
    }
} );

window.Object.defineProperty( Element.prototype, 'documentOffsetLeft', {
    get: function () {
        return this.offsetLeft + ( this.offsetParent ? this.offsetParent.documentOffsetLeft : 0 );
    }
} );

function al_handlerMM(e){
   if (!e) var e = window.event;
if (e.pageX || e.pageY)
   {
      al_x = e.pageX;
      al_y = e.pageY;
   }
else if (e.clientX || e.clientY)
   {
      al_x = e.clientX;
      al_y = e.clientY;
      if (al_isIE)
      {
         al_x += document.body.scrollLeft;
         al_y += document.body.scrollTop;
      }
   }
}

document.onmousemove = al_handlerMM;

al_x = -1;
al_y = -1;
al_timeout_ref=0;
al_timeout_in_ref=0;
al_overdiv=0;
al_overlink=0;
al_id=-1;

function al_div_out () {
   if (al_timeout_ref == 0) al_timeout_ref = setTimeout('al_timeout()',1000);
   al_overdiv = 0;
}

function al_div_in () {
   al_overdiv = 1;
   if (al_timeout_ref != 0) clearTimeout(al_timeout_ref);
   al_timeout_ref = 0;
}

function al_link_out () {
   if ((al_overdiv == 0) && (al_overlink == 1)) {
      if (al_timeout_ref == 0) al_timeout_ref = setTimeout('al_timeout()',1000);
      if (al_timeout_in_ref != 0) clearTimeout(al_timeout_in_ref);
      al_timeout_in_ref = 0;
   }
   al_overlink = 0;

}

function al_link_in (id, content) {
   if ((al_x != -1) && ((id != al_id) || ((al_overlink == 0) && (al_overdiv == 0) && (al_timeout_ref == 0)))) {
      al_content = content;
      if (al_timeout_in_ref == 0) setTimeout('al_show('+id+')',500)
   }
   if (al_timeout_ref!= 0) clearTimeout(al_timeout_ref);
   al_timeout_ref = 0;
   al_overlink = 1;

}

function al_timeout() {

   if ((al_overdiv == 0) && (al_overlink == 0) && (al_timeout_ref!= 0)) {
      al_timeout_ref=0;
      if (document.getElementById) { // DOM3 = IE5, NS6
         var menu_element = document.getElementById('al_popup');
         menu_element.style.visibility = 'hidden';
      }
   }
}

function al_show( id ) {

   if ((al_overlink == 1) || (al_overdiv ==1)) {
      if (al_timeout_ref!= 0) clearTimeout(al_timeout_ref);
      al_timeout_ref = 0;
      al_timeout_in_ref = 0;
      al_id = id;

      if (document.getElementById && (al_x != -1)) { // DOM3 = IE5, NS6
         var menu_element = document.getElementById('al_popup');
         
         if (al_y> 10) al_y -= 5;
         al_x += 15;
         menu_element.style.left = al_x + "px";
         menu_element.style.top = al_y + "px";
         menu_element.style.visibility = 'visible';
         menu_element.innerHTML= al_content;
         menu_element.style.display = 'block';

         actual_x = menu_element.documentOffsetLeft;
         actual_y = menu_element.documentOffsetTop;

         al_x = al_x + (al_x - actual_x);
         al_y = al_y + (al_y - actual_y);
         menu_element.style.left = al_x + "px";
         menu_element.style.top = al_y + "px";

      }
   } else {
      al_id = -1;
   }
}

function al_gen_multi (id, term, def, chan) {
   var content = "";
   var def = def.split(",");
   
   if ( AmazonLinkMulti.channels[chan] == undefined) {
      chan = 'default';
   }
   for (var cc in AmazonLinkMulti.country_data) {
      var type = term[cc].substr(0,1);
      var arg  = term[cc].substr(2);
      var tld  = AmazonLinkMulti.country_data[cc].tld;
      var tag  = AmazonLinkMulti.channels[chan]['tag_'+cc];
      
      if ( def.indexOf(cc) == -1 ) {
         url = AmazonLinkMulti.link_templates[type];
         url = url.replace(/%CC%#/g, '');
         url = url.replace(/%CC%/g, cc);
         url = url.replace(/%MANUAL_CC%/g, cc);
         url = url.replace(/%ARG%/g, arg);
         url = url.replace(/%TLD%/g, tld);
         url = url.replace(/%TAG%/g, tag);
         content = content +'<a rel="nofollow" '+AmazonLinkMulti.target+' href="' + url + '"><img src="' + AmazonLinkMulti.country_data[cc].flag + '"></a>';
      }
   }
   al_link_in (id, content);
}