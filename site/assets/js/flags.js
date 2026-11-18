/* BezanBerim – flags & countries (Europe only) */
(function(){
"use strict";
// ---------- flags (simplified inline SVG) ----------
function s(vb, body){ return '<svg viewBox="'+vb+'" aria-hidden="true" preserveAspectRatio="xMidYMid slice">'+body+'</svg>'; }
function hStripes(cols){ var h=20/cols.length; return cols.map(function(c,i){return '<rect y="'+(i*h)+'" width="30" height="'+(h+0.05)+'" fill="'+c+'"/>';}).join(''); }
function vStripes(cols){ var w=30/cols.length; return cols.map(function(c,i){return '<rect x="'+(i*w)+'" width="'+(w+0.05)+'" height="20" fill="'+c+'"/>';}).join(''); }
function nordic(bg, cross, inner){
  var b='<rect width="30" height="20" fill="'+bg+'"/><rect x="8" width="5" height="20" fill="'+cross+'"/><rect y="7.5" width="30" height="5" fill="'+cross+'"/>';
  if(inner) b+='<rect x="9.25" width="2.5" height="20" fill="'+inner+'"/><rect y="8.75" width="30" height="2.5" fill="'+inner+'"/>';
  return s('0 0 30 20', b);
}
function ukInner(){ return '<rect width="60" height="30" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#C8102E" stroke-width="2"/><rect x="25" width="10" height="30" fill="#fff"/><rect y="10" width="60" height="10" fill="#fff"/><rect x="27" width="6" height="30" fill="#C8102E"/><rect y="12" width="60" height="6" fill="#C8102E"/>'; }
window.FLAG={
  se:nordic('#006AA7','#FECC02'),
  dk:nordic('#C8102E','#fff'),
  fi:nordic('#fff','#002F6C'),
  no:nordic('#BA0C2F','#fff','#00205B'),
  de:s('0 0 30 20',hStripes(['#000','#DD0000','#FFCE00'])),
  at:s('0 0 30 20',hStripes(['#C8102E','#fff','#C8102E'])),
  nl:s('0 0 30 20',hStripes(['#AE1C28','#fff','#21468B'])),
  lu:s('0 0 30 20',hStripes(['#EA141D','#fff','#51ADDA'])),
  hu:s('0 0 30 20',hStripes(['#CE2939','#fff','#477050'])),
  pl:s('0 0 30 20',hStripes(['#fff','#DC143C'])),
  fr:s('0 0 30 20',vStripes(['#002395','#fff','#ED2939'])),
  it:s('0 0 30 20',vStripes(['#009246','#fff','#CE2B37'])),
  be:s('0 0 30 20',vStripes(['#000','#FAE042','#ED2939'])),
  ie:s('0 0 30 20',vStripes(['#169B62','#fff','#FF883E'])),
  pt:s('0 0 30 20','<rect width="30" height="20" fill="#DA291C"/><rect width="12" height="20" fill="#046A38"/><circle cx="12" cy="10" r="3.4" fill="#FFE900"/><circle cx="12" cy="10" r="2.2" fill="#DA291C"/>'),
  es:s('0 0 30 20','<rect width="30" height="20" fill="#AA151B"/><rect y="5" width="30" height="10" fill="#F1BF00"/>'),
  ch:s('0 0 30 20','<rect width="30" height="20" fill="#DA291C"/><rect x="13" y="4" width="4" height="12" fill="#fff"/><rect x="9" y="8" width="12" height="4" fill="#fff"/>'),
  cz:s('0 0 30 20','<rect width="30" height="10" fill="#fff"/><rect y="10" width="30" height="10" fill="#D7141A"/><path d="M0,0 L15,10 L0,20Z" fill="#11457E"/>'),
  gb:s('0 0 60 30',ukInner()),
  tr:s('0 0 30 20','<rect width="30" height="20" fill="#E30A17"/><circle cx="11" cy="10" r="5" fill="#fff"/><circle cx="12.3" cy="10" r="4" fill="#E30A17"/><path d="M15.4,10 L18.6,8.9 L16.6,11.6 L16.6,8.4 L18.6,11.1Z" fill="#fff"/>'),
  gr:s('0 0 30 20',(function(){var r='',h=20/9;for(var i=0;i<9;i++)r+='<rect y="'+(i*h)+'" width="30" height="'+(h+0.05)+'" fill="'+(i%2?'#fff':'#0D5EAF')+'"/>';r+='<rect width="'+(5*h)+'" height="'+(5*h)+'" fill="#0D5EAF"/><rect x="'+(2*h)+'" width="'+h+'" height="'+(5*h)+'" fill="#fff"/><rect y="'+(2*h)+'" width="'+(5*h)+'" height="'+h+'" fill="#fff"/>';return r;})())
};
// code, Persian name, English name (Europe only)
window.COUNTRIES=[
  ["all","همه اروپا","All Europe"],["se","سوئد","Sweden"],["de","آلمان","Germany"],["gb","بریتانیا","United Kingdom"],
  ["nl","هلند","Netherlands"],["no","نروژ","Norway"],["dk","دانمارک","Denmark"],["fr","فرانسه","France"],
  ["at","اتریش","Austria"],["ch","سوئیس","Switzerland"],["be","بلژیک","Belgium"],["fi","فنلاند","Finland"],
  ["it","ایتالیا","Italy"],["es","اسپانیا","Spain"],["tr","ترکیه","Turkey"],["gr","یونان","Greece"],
  ["cz","چک","Czechia"],["ie","ایرلند","Ireland"],["pl","لهستان","Poland"],["pt","پرتغال","Portugal"],
  ["hu","مجارستان","Hungary"],["lu","لوکزامبورگ","Luxembourg"]
];
})();
