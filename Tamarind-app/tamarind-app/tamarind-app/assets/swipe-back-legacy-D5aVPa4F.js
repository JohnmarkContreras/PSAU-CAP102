System.register(["./index-legacy-D6awhScX.js"],function(t,e){"use strict";var n,r,i;return{setters:[t=>{n=t.i,r=t.e,i=t.j}],execute:function(){
/*!
             * (C) Ionic http://ionicframework.com - MIT License
             */
t("createSwipeBackGesture",(t,e,s,o,c)=>{const a=t.ownerDocument.defaultView;let u=n(t);const l=t=>u?-t.deltaX:t.deltaX;return r({el:t,gestureName:"goback-swipe",gesturePriority:101,threshold:10,canStart:r=>(u=n(t),(t=>{const{startX:e}=t;return u?e>=a.innerWidth-50:e<=50})(r)&&e()),onStart:s,onMove:t=>{const e=l(t)/a.innerWidth;o(e)},onEnd:t=>{const e=l(t),n=a.innerWidth,r=e/n,s=(t=>u?-t.velocityX:t.velocityX)(t),o=s>=0&&(s>.2||e>n/2),d=(o?1-r:r)*n;let h=0;if(d>5){const t=d/Math.abs(s);h=Math.min(t,540)}c(o,r<=0?.01:i(0,r,.9999),h)}})})}}});
