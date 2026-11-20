/* BezanBerim · بزن بریم — vanilla JS, no framework (same approach as onja.ca). */
(function(){
"use strict";
var FLAG=window.FLAG, COUNTRIES=window.COUNTRIES;
var NAME={}, EN={}; COUNTRIES.forEach(function(c){NAME[c[0]]=c[1];EN[c[0]]=c[2];});
var $=function(id){return document.getElementById(id);};

// ---------- state & helpers ----------
var st={groups:[],gkind:"all",country:"all",type:"all",when:"all",cat:"all",city:"",q:"",events:[],pending:[],sources:[],subs:[],contact:"",uid:null,canEdit:false,canWrite:null,loaded:false,db:null,openSrc:null,day:""};
function esc(t){return String(t==null?"":t).replace(/[&<>"']/g,function(c){return {"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c];});}
function safeIcon(u){ u=String(u||""); return /^uploads\/icons\/[a-f0-9]{16,40}r?\.png$/.test(u)?u:""; }
function safeUrl(u){ u=String(u||""); return /^https?:\/\//i.test(u)?u:""; }
function safeImg(u){ u=String(u||""); if(/^thumbs\/[a-z0-9._-]+\.(jpe?g|png|webp)$/i.test(u)) return u; return /^https:\/\/(www\.javantik\.com\/cdn\/events-image|volek\.events\/wp-content\/uploads|www\.modernamuseet\.se\/app\/uploads|www\.kingsplace\.co\.uk\/wp-content\/uploads|rrorwfctzuxjwjbtuhvt\.supabase\.co\/storage\/v1\/object\/public\/posters)\/[A-Za-z0-9._\/-]+\.(jpe?g|png|webp)$/i.test(u)?u:""; }
function iso(d){return d.getFullYear()+"-"+String(d.getMonth()+1).padStart(2,"0")+"-"+String(d.getDate()).padStart(2,"0");}
function todayISO(){return iso(new Date());}
function plusDays(n){var d=new Date();d.setDate(d.getDate()+n);return iso(d);}
function parseD(s){ if(!/^\d{4}-\d{2}-\d{2}$/.test(s||""))return null; var p=s.split("-"); return new Date(+p[0],+p[1]-1,+p[2]); }
function fmt(d,o,j){ try{ return new Intl.DateTimeFormat(j?"fa-IR":"fa-IR-u-ca-gregory-nu-arabext",o).format(d); }catch(e){ return d.toDateString(); } }
function faNum(n){ try{ return Number(n).toLocaleString("fa-IR"); }catch(e){ return String(n); } }
var EN_MONTHS=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
function weekendRange(){ var d=new Date(), wd=d.getDay(); // 0 Sun … 5 Fri 6 Sat
  var start=new Date(d), end=new Date(d);
  if(wd===0){ /* Sunday: today only */ }
  else if(wd>=5){ end.setDate(d.getDate()+(7-wd)); }
  else { start.setDate(d.getDate()+(5-wd)); end.setDate(d.getDate()+(7-wd)); }
  return [iso(start),iso(end)];
}

// ---------- categories (derived from the title) ----------
var CATS=[["all","همه‌ی دسته‌ها"],["concert","کنسرت"],["party","پارتی و شب"],["celebration","یلدا و جشن‌ها"],["culture","فرهنگی و هنری"]];
function catOf(e){
  var t=(e.title+" "+(e.note||"")).toLowerCase();
  if(/yalda|یلدا|new year|nowruz|newroz|norooz|x-mas|christmas|flag day|ala kurdistan|celebration|جشن|homecoming/.test(t)) return "celebration";
  if(/talk|book|exhibition|film|screening|santur|author|circle|yoga|theat|standup|stand-up|کتاب|نمایش|فیلم/.test(t)) return "culture";
  if(/party|night|\bdj\b|club|afro|ladies|tjej|fest\b|parti|پارتی/.test(t)) return "party";
  return "concert";
}

// ---------- language words ----------
var KURD=["kurd","kurdi","kurdî","kurdisk","kurdiska","kurdisch","koerdisch","kurde","curdo","kurdo","kürt","kürtçe","kurdsk","kurdilainen","κουρδ","کرد","کورد","کوردی","کردی","newroz","نەورۆز"];
var PERS=["pers","iran","farsi","afghan","afgan","afghansk","afghanska","persisk","persiska","iransk","iranska","persisch","iranisch","afghanisch","perzisch","iraans","afghaans","persan","iranien","persiano","iraniano","afghano","persa","iraní","afgano","farsça","iranlı","persialainen","iranilainen","afganistan","περσ","ιραν","perský","íránský","فارسی","ایرانی","افغان","افغانی"];
function typeFromWord(q){
  q=q.trim(); if(q.indexOf(" ")>=0||q.length<3) return null;
  if(KURD.some(function(w){return q.indexOf(w)===0||w.indexOf(q)===0;})) return "kurdish";
  if(PERS.some(function(w){return q.indexOf(w)===0||w.indexOf(q)===0;})) return "persian";
  return null;
}
function normH(v){ return String(v||"").toLowerCase().replace(/^https?:\/\/(www\.)?/,"").replace(/^(instagram|facebook)\.com\//,"").replace(/^(chat\.whatsapp\.com|whatsapp\.com\/(channel|invite|c)|wa\.me|t\.me|telegram\.me)\//,"").replace(/^@/,"").replace(/[\/?#].*$/,"").trim(); }
function eventsFor(src){
  var key=normH(src.handle);
  return st.events.filter(function(e){
    if(!NAME[e.country]) return false;
    if((e.sourceIds||[]).indexOf(src.id)>=0) return true;
    if(key.length<4) return false;
    return [e.organizer,e.source,e.url].some(function(v){ return String(v||"").toLowerCase().indexOf(key)>=0; });
  }).sort(function(a,b){return (a.date||"").localeCompare(b.date||"");});
}

// ---------- filtering ----------
function isUpcoming(e){ var end=e.endDate||e.date; return !!end && end>=todayISO() && !!NAME[e.country] && e.country!=="all"; }
function inWhen(e,w){
  var t=todayISO();
  if(w==="all") return true;
  if(w==="new") return (e.addedAt||"")>=plusDays(-3);
  var s=e.date, en=e.endDate||e.date;
  if(w==="today") return s<=t && en>=t;
  if(w==="weekend"){ var r=weekendRange(); return s<=r[1] && en>=r[0]; }
  if(w==="week") return s<=plusDays(7);
  if(w==="month") return s<=plusDays(30);
  return true;
}
function baseFilter(e){ return isUpcoming(e) && (st.country==="all"||e.country===st.country); }
function visible(){
  return st.events.filter(function(e){
    if(!baseFilter(e)) return false;
    if(!inWhen(e,st.when)) return false;
    if(st.day && !(e.date<=st.day && (e.endDate||e.date)>=st.day)) return false;
    if(st.type!=="all" && !(e.type===st.type || e.type==="both")) return false;
    if(st.cat!=="all" && catOf(e)!==st.cat) return false;
    if(st.city && (e.city||"")!==st.city) return false;
    if(st.q){
      var syn=typeFromWord(st.q);
      if(syn){ if(!(e.type===syn||e.type==="both")) return false; }
      else { var hay=[e.title,e.venue,e.city,e.organizer,e.source,(e.sourceIds||[]).join(" "),e.note,NAME[e.country],EN[e.country]].join(" ").toLowerCase(); if(hay.indexOf(st.q)<0) return false; }
    }
    return true;
  }).sort(byDate);
}
function byDate(a,b){ var ao=a.ongoing?0:1, bo=b.ongoing?0:1; if(ao!==bo) return ao-bo; return (a.date||"").localeCompare(b.date||"") || (a.time||"").localeCompare(b.time||""); }
function upcomingCount(code){ return st.events.filter(function(e){return isUpcoming(e)&&(code==="all"||e.country===code);}).length; }

// ---------- card ----------
function card(e){
  var d=parseD(e.date), dl=parseD(e.endDate||e.date), url=safeUrl(e.url), img=safeImg(e.image);
  var cls=e.type==="kurdish"?"kurdish":(e.type==="both"?"both":"");
  var tagTxt=e.type==="kurdish"?"کوردی":(e.type==="both"?"کوردی · ایرانی":"ایرانی");
  var isIG=e.source==="instagram"||/instagram\.com/i.test(e.url||"");
  var pill = (e.ongoing&&dl) ? 'تا <b>'+fmt(dl,{day:"numeric"})+'</b> '+fmt(dl,{month:"long"})
           : d ? fmt(d,{weekday:"long"})+' <b>'+fmt(d,{day:"numeric"})+'</b> <i>'+fmt(d,{month:"long"})+' '+fmt(d,{year:"numeric"})+'</i>' : 'تاریخ نامشخص';
  var media='<div class="ph"><b>'+esc(e.city||EN[e.country]||"")+'</b><small>'+esc(EN[e.country]||"")+'</small></div>'+(img?'<img src="'+esc(img)+'" alt="" loading="lazy" onerror="this.remove()">':'');
  var place=[e.city, NAME[e.country]].filter(Boolean).join("، ");
  var jal=(d&&!e.ongoing)?fmt(d,{day:"numeric",month:"long"},true):"";
  var timeLine=[e.time?'<span class="ltr">'+esc(e.time)+'</span>':'', jal?esc(jal)+' (شمسی)':'', e.age?'<span class="ltr">'+esc(e.age)+'</span>':''].filter(Boolean).join(' · ');
  var price=e.price||"See ticket page", unk=/see |unknown|tba/i.test(price);
  var inner='<div class="img">'+media+'<span class="tag '+cls+'">'+tagTxt+'</span>'+(e.test?'<span class="testtag">TEST</span>':'')+(isIG?'<span class="igb" title="Instagram"><svg class="ico"><use href="#i-ig"/></svg></span>':'')+'</div>'+
    '<span class="datepill">'+pill+'</span>'+
    '<div class="body"><h3 dir="auto">'+esc(e.title)+'</h3>'+
    '<div class="where">'+(FLAG[e.country]||'')+'<span>'+esc(place)+'</span></div>'+
    (e.venue?'<div class="venue" dir="auto" title="'+esc(e.venue)+'">'+esc(e.venue)+'</div>':'')+
    (timeLine?'<div class="time">'+timeLine+'</div>':'')+
    (e.organizer?'<div class="org" dir="auto">'+esc(e.organizer)+'</div>':'')+
    '<div class="foot"><span class="price'+(unk?' unk':'')+'" dir="auto" title="'+esc(price)+'">'+esc(unk?"قیمت در صفحه‌ی برنامه":price)+'</span>'+'</div></div>';
  return url? '<div class="ev '+cls+'" role="link" tabindex="0" data-go="'+esc(url)+'">'+inner+'</div>' : '<div class="ev '+cls+'">'+inner+'</div>';
}

// ---------- render ----------
function renderCountrySelect(){
  var sel=$("countrySel");
  sel.innerHTML=COUNTRIES.map(function(c){ return '<option value="'+c[0]+'"'+(st.country===c[0]?' selected':'')+'>'+esc(c[1])+'</option>'; }).join("");
}
function renderFlags(){
  $("flags").innerHTML=COUNTRIES.map(function(c){
    var n=st.loaded?upcomingCount(c[0]):"";
    var pic=c[0]==="all"?'<span class="globe" aria-hidden="true">EU</span>':FLAG[c[0]];
    var zero=st.loaded&&n===0&&c[0]!=="all";
    return '<button type="button" class="flag'+(zero?' zero':'')+'" data-c="'+c[0]+'" title="'+esc(c[1]+" · "+c[2])+'" aria-label="'+esc(c[1])+(n===""?"":"، "+n+" برنامه")+'" aria-pressed="'+(st.country===c[0])+'">'+pic+'<span class="n">'+(n===""?"&nbsp;":faNum(n)+'<small>برنامه</small>')+'</span></button>';
  }).join("");
}
function renderHero(){
  var withImg=st.events.filter(function(e){return baseFilter(e)&&safeImg(e.image);}).sort(byDate);
  var wk=st.events.filter(function(e){return baseFilter(e)&&inWhen(e,"weekend");});
  var pics=(wk.filter(function(e){return safeImg(e.image);}).concat(withImg)).slice(0,3);
  var spans=$("stack").children;
  for(var i=0;i<3;i++){ spans[i].style.backgroundImage=pics[i]?'url("'+safeImg(pics[i].image)+'")':''; }
  if(st.loaded) $("weekendTxt").textContent=wk.length? faNum(wk.length)+" برنامه همین آخر هفته"+(st.country==="all"?" در اروپا":" در "+NAME[st.country])+" — کنسرت، پارتی و جشن. بکش پایین." : "این آخر هفته برنامه‌ای ثبت نشده؛ نزدیک‌ترین برنامه‌ها پایین صفحه‌اند.";
}
function renderRail(){
  var place=st.country==="all"?"اروپا":NAME[st.country];
  var up=st.events.filter(baseFilter).sort(byDate).filter(function(e){return !e.ongoing;});
  // Posters first (like onja's event rail), then fill with the nearest other events.
  var pics=up.filter(function(e){return safeImg(e.image);}), rest=up.filter(function(e){return !safeImg(e.image);});
  var rows=pics.slice(0,12); if(rows.length<4) rows=rows.concat(rest.slice(0,4-rows.length)).sort(byDate);
  $("soonTitle").textContent="برنامه‌های داغ پیش رو در "+place;
  $("rail").innerHTML=!st.loaded?'<div class="empty">در حال بارگذاری…</div>':(rows.length?rows.map(card).join(""):'<div class="empty">فعلاً برنامه‌ای اینجا نیست.</div>');
  updateArrows();
}
function renderCats(){
  var base=st.events.filter(baseFilter), n={all:base.length};
  base.forEach(function(e){var c=catOf(e);n[c]=(n[c]||0)+1;});
  $("cats").innerHTML=CATS.map(function(c){ return '<button type="button" class="chip" data-cat="'+c[0]+'" aria-pressed="'+(st.cat===c[0])+'">'+c[1]+(st.loaded?' <span style="opacity:.6">'+faNum(n[c[0]]||0)+'</span>':'')+'</button>'; }).join("");
}
function renderCities(){
  var cnt={};
  st.events.forEach(function(e){ if(baseFilter(e)&&e.city) cnt[e.city]=(cnt[e.city]||0)+1; });
  var cities=Object.keys(cnt).sort(function(a,b){ return cnt[b]-cnt[a] || a.localeCompare(b); });
  if(st.city && !cnt[st.city]) st.city="";
  $("city").innerHTML='<option value="">همه شهرها'+(cities.length?' ('+faNum(cities.length)+')':'')+'</option>'+cities.map(function(c){return '<option value="'+esc(c)+'"'+(c===st.city?' selected':'')+'>'+esc(c)+' · '+faNum(cnt[c])+'</option>';}).join("");
}
function renderList(){
  var el=$("list");
  $("evTitle").textContent="رویدادها در "+(st.country==="all"?"سراسر اروپا":NAME[st.country]);
  if(!st.loaded){ el.innerHTML='<div class="empty">در حال بارگذاری برنامه‌ها…</div>'; $("count").textContent=""; return; }
  if(!st.db){ el.innerHTML='<div class="note">برای دیدن برنامه‌ها وارد claude.ai شوید.</div>'; return; }
  var rows=visible();
  $("count").textContent=faNum(rows.length)+" برنامه";
  if(!rows.length){ el.innerHTML='<div class="empty">با این فیلترها برنامه‌ای پیدا نشد. صفحه‌ای می‌شناسی که برنامه می‌گذارد؟ <a href="#add">اضافه‌اش کن</a>.</div>'; return; }
  var html="", cur="", buf=[];
  var ongoing=rows.filter(function(e){return e.ongoing;}), dated=rows.filter(function(e){return !e.ongoing;});
  if(ongoing.length) html+='<div class="month">برنامه‌های جاری و هفتگی</div><div class="grid">'+ongoing.map(card).join("")+'</div>';
  dated.forEach(function(e){
    var d=parseD(e.date), key=d?fmt(d,{month:"long",year:"numeric"}):"تاریخ نامشخص";
    if(key!==cur){ if(buf.length) html+='<div class="grid">'+buf.join("")+'</div>'; buf=[]; cur=key; html+='<div class="month">'+esc(key)+'</div>'; }
    buf.push(card(e));
  });
  if(buf.length) html+='<div class="grid">'+buf.join("")+'</div>';
  el.innerHTML=html;
}
function renderNav(){
  document.querySelectorAll("#nav button[data-nav]").forEach(function(b){
    var k=b.getAttribute("data-nav"), on=false;
    if(["new","today","weekend"].indexOf(k)>=0) on=st.when===k;
    else if(["concert","party","celebration","culture"].indexOf(k)>=0) on=st.cat===k;
    else if(k==="kurdish"||k==="persian") on=st.type===k;
    b.setAttribute("aria-pressed",String(on));
  });
  $("typeSel").value=st.type; $("whenSel").value=st.when;
}
function renderFoot(){
  if(!st.loaded) return;
  var up=st.events.filter(isUpcoming), cs={}, cities={};
  up.forEach(function(e){cs[e.country]=1; if(e.city) cities[e.city]=1;});
  $("footStats").innerHTML='<b>'+faNum(up.length)+'</b> برنامه · <b>'+faNum(Object.keys(cs).length)+'</b> کشور · <b>'+faNum(Object.keys(cities).length)+'</b> شهر';
}

// ---------- groups & associations ----------
var GK=[["all","همه"],["انجمن","انجمن‌ها"],["گروه","گروه‌ها"]];
function renderGroups(){
  var el=$("groupsList"); if(!el) return;
  $("grTitle").textContent="گروه‌ها و انجمن‌ها"+(st.country==="all"?" در اروپا":" در "+NAME[st.country]);
  var base=st.groups.filter(function(g){ return NAME[g.country] && (st.country==="all"||g.country===st.country||g.country==="all"); });
  $("grChips").innerHTML=GK.map(function(k){ var n=base.filter(function(g){return gMatch(g,k[0]);}).length; return '<button type="button" class="chip" data-gk="'+k[0]+'" aria-pressed="'+(st.gkind===k[0])+'">'+k[1]+' <span style="opacity:.6">'+faNum(n)+'</span></button>'; }).join("");
  var rows=base.filter(function(g){return gMatch(g,st.gkind);}).sort(function(a,b){return (a.order||99)-(b.order||99)||String(a.name).localeCompare(String(b.name));});
  if(!st.loaded){ el.innerHTML='<div class="empty">در حال بارگذاری…</div>'; return; }
  if(!rows.length){ el.innerHTML='<div class="empty" style="grid-column:1/-1">هنوز گروه یا انجمنی'+(st.country==="all"?'':' در '+esc(NAME[st.country]))+' ثبت نشده. <a href="#add">معرفی کن</a>.</div>'; return; }
  el.innerHTML=rows.map(function(g){
    var url=safeUrl(g.url), cls=g.type==="kurdish"?"kurdish":(g.type==="both"?"both":"");
    var ini=String(g.nameLatin||g.name||"?").replace(/^(Kulturföreningen|KUF)\s+/i,"").trim().charAt(0).toUpperCase();
    var place=[g.city, g.kind==="زیرگروه"?"":NAME[g.country]].filter(Boolean).join("، ");
    var avIn=g.logo?'<img src="'+esc(g.logo)+'" alt="" loading="lazy">':esc(ini);
    var cov=g.cover?'<img class="cover" src="'+esc(g.cover)+'" alt="" loading="lazy">':'';
    var avHtml=(!g.cover&&(g.logo||g.kind!=="زیرگروه"))?'<span class="av'+(g.logo?' logo':'')+(/(^uploads\/icons\/[a-f0-9]+r\.png|-round\.png)$/.test(g.logo||"")?' round':'')+'">'+avIn+'</span>':'';
    var kindHtml=(g.kind&&g.kind!=="زیرگروه")?'<span class="kind">'+esc(g.kind)+'</span>':'';
    var descGo=(g.desc&&g.desc!==g.name?'<p dir="auto">'+esc(g.desc)+'</p>':'')+
      '';
    var titleHtml='<h3 dir="auto">'+esc(g.name)+'</h3>'+(g.nameLatin&&g.nameLatin!==g.name&&!/^(https?:\/\/|www\.|[a-z0-9.-]+\.(com|me|org|net)\/)/i.test(g.nameLatin)?'<span class="gl">'+esc(g.nameLatin)+'</span>':'')+
      '<span class="gw">'+(FLAG[g.country]||'')+'<span>'+esc(place)+'</span>'+kindHtml+'</span>';
    var inner=cov+'<span class="grow">'+avHtml+'<span class="gb">'+titleHtml+descGo+'</span></span>';
    return url?'<div class="gcard '+cls+'" role="link" tabindex="0" data-go="'+esc(url)+'">'+inner+'</div>':'<div class="gcard '+cls+'">'+inner+'</div>';
  }).join("");
}
function gMatch(g,k){ if(k==="all") return true; if(k==="گروه") return g.kind==="گروه"||g.kind==="زیرگروه"; return g.kind===k; }
$("grChips").addEventListener("click",function(ev){ var b=ev.target.closest(".chip"); if(!b) return; st.gkind=b.getAttribute("data-gk"); renderGroups(); });

function gid(src){ return String(src.id||"").replace(/^(instagram|facebook|website)-/,"").replace(/[^a-z0-9._-]+/gi,"-").toLowerCase().slice(0,120)||("g"+Date.now()); }
function inGroups(src){ return st.groups.some(function(g){ return g.sourceId===src.id || (safeUrl(g.url)&&normH(g.url)===normH(src.url)); }); }
async function addToGroups(src){
  var h=String(src.handle||"").replace(/^https?:\/\/(www\.)?(instagram|facebook)\.com\//,"").replace(/^https?:\/\/(t|telegram)\.me\//,"").replace(/^@/,"").replace(/\/+$/,"");
  var type=src.type==="kurdish"?"kurdish":(src.type==="persian"||src.type==="afghan")?"persian":"both";
  await st.db.doc("communities/"+gid(src)).set({name:src.note||h,nameLatin:(src.platform==="whatsapp"||src.platform==="telegram"||src.platform==="website"?"":h),kind:src.kind||"گروه",type:type,city:src.city||"",country:src.country||"all",url:src.url||"",platform:src.platform||"",desc:src.note||"",order:50,sourceId:src.id,logo:safeIcon(src.icon)||"",addedAt:new Date().toISOString().slice(0,10)});
}

// ---------- admin & submissions ----------
function keyFor(p,h){ return (p+"-"+String(h).toLowerCase().replace(/^https?:\/\/(www\.)?/,"").replace(/^(instagram|facebook)\.com\//,"").replace(/^(chat\.whatsapp\.com|whatsapp\.com\/(channel|invite|c)|wa\.me|t\.me|telegram\.me)\//,"").replace(/[?#].*$/,"").replace(/^@/,"").replace(/\/+$/,"").replace(/[^a-z0-9._-]+/g,"-").replace(/^-+|-+$/g,"")).slice(0,180); }
function decided(k){ return st.sources.find(function(s){return s.id===k;}); }
function renderSources(){
  var el=$("sources"); if(!st.canEdit||!el) return;
  var list=st.sources.filter(function(s){return s.status==="approved";}).sort(function(a,b){return (a.country+a.handle).localeCompare(b.country+b.handle);});
  $("trackedN").textContent="("+faNum(list.length)+")";
  if(!list.length){ el.innerHTML='<li>هنوز صفحه‌ای تحت پیگیری نیست.</li>'; return; }
  var t=todayISO();
  el.innerHTML=list.map(function(s){
    var u=safeUrl(s.url), evs=eventsFor(s), up=evs.filter(function(e){return (e.endDate||e.date)>=t;}), open=st.openSrc===s.id;
    var sub= open ? '<div class="srcev">'+(evs.length? evs.map(function(e){var d=parseD(e.date),past=(e.endDate||e.date)<t;return '<div class="srcrow'+(past?' past':'')+'"><span class="sd">'+(d?d.getDate()+" "+EN_MONTHS[d.getMonth()]+" "+d.getFullYear():"")+'</span><span>'+(safeUrl(e.url)?'<a href="'+esc(safeUrl(e.url))+'" target="_blank" rel="noopener">'+esc(e.title)+'</a>':esc(e.title))+'</span><span class="sm">'+esc((e.city?e.city+", ":"")+(EN[e.country]||""))+'</span></div>';}).join("") : '<div class="sm">هنوز برنامه‌ای از این صفحه ثبت نشده.</div>')+'</div>' : '';
    return '<li class="srcli"><div class="srctop"><span class="ltr" style="text-align:left"><span class="pf">'+esc(s.platform)+'</span><br>'+(u?'<a href="'+esc(u)+'" target="_blank" rel="noopener">'+esc(s.handle)+'</a>':esc(s.handle))+'</span><span style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;color:var(--muted)">'+esc((s.city?s.city+"، ":"")+(s.country==="all"?"همه کشورها":(NAME[s.country]||s.country)))+'<button class="btn small'+(open?'':' ghost')+'" data-open="'+esc(s.id)+'" aria-expanded="'+open+'">'+faNum(up.length)+' پیش رو · '+faNum(evs.length)+' کل</button>'+(inGroups(s)?'<span class="sm">در گروه‌ها ✓</span>':'<button class="btn small ghost" data-addgroup="'+esc(s.id)+'">افزودن به گروه‌ها</button>')+'<button class="btn small ghost" data-stop="'+esc(s.id)+'">توقف پیگیری</button></span></div>'+sub+'</li>';
  }).join("");
}
function renderMine(){
  var el=$("mine");
  if(!st.canEdit){ el.innerHTML=""; return; }
  var mineDoc=st.subs.find(function(d){return d.id===st.uid;});
  var items=mineDoc&&mineDoc.items||[];
  if(!items.length){ el.innerHTML=""; return; }
  el.innerHTML='<h3 style="margin:22px 0 8px;font-size:15px">صفحه‌هایی که فرستادی</h3><ul class="sources">'+items.map(function(it){
    var s=decided(it.key), status=s?(s.status==="approved"?"تأیید شد ✓":"اضافه نشد"):"در انتظار بررسی";
    return '<li><span class="ltr">'+esc(it.handle)+'</span><span style="color:var(--muted)">'+status+'</span></li>';
  }).join("")+'</ul>';
}
function renderQueue(){
  var panel=$("adminPanel");
  panel.hidden=!st.canEdit; if(!st.canEdit) return;
  var pend=[];
  st.subs.forEach(function(d){ (d.items||[]).forEach(function(it){ if(!decided(it.key)) pend.push(it); }); });
  $("queue").innerHTML=pend.length? pend.map(function(it){
    var u=safeUrl(it.url);
    return '<div class="qitem">'+(safeIcon(it.icon)?'<img src="'+esc(safeIcon(it.icon))+'" alt="" width="48" height="48" style="border-radius:10px;border:1px solid var(--line);background:#fff">':'')+'<div><div class="who ltr" style="text-align:left">'+(u?'<a href="'+esc(u)+'" target="_blank" rel="noopener">'+esc(it.handle)+'</a>':esc(it.handle))+'</div><div style="color:var(--muted);font-size:13.5px">'+esc(it.platform)+' · '+esc(it.country==="all"?"همه کشورها":(NAME[it.country]||it.country))+(it.city?' · '+esc(it.city):'')+' · '+esc(({persian:'ایرانی',afghan:'افغانی',kurdish:'کوردی',all:'همه',both:'ایرانی و کوردی'})[it.type]||it.type)+(it.kind?' · '+esc(it.kind):'')+(it.note?' · «'+esc(it.note)+'»':'')+'</div></div><div class="act"><button class="btn small ok" data-act="approve" data-k="'+esc(it.key)+'">تأیید</button><button class="btn small ghost" data-act="approve-group" data-k="'+esc(it.key)+'">تأیید + گروه‌ها</button><button class="btn small no" data-act="reject" data-k="'+esc(it.key)+'">رد</button></div></div>';
  }).join("") : '<div class="empty">صفحه‌ای در انتظار بررسی نیست.</div>';
  var c=$("contact"); if(document.activeElement!==c) c.value=st.contact||"";
}

function renderPending(){
  var el=$("pending"); if(!el||!st.canEdit) return;
  var list=st.pending.filter(function(e){return e.status==="pending";}).sort(function(a,b){return String(a.date).localeCompare(String(b.date));});
  $("pendN").textContent="("+faNum(list.length)+")";
  el.innerHTML=list.length? list.map(function(e){
    var ok=/^\d{4}-\d{2}-\d{2}$/.test(e.date||"")&&e.title&&e.country&&/^[a-z]{2}$/i.test(e.country);
    var u=safeUrl(e.url);
    return '<div class="qitem"><div><div class="who">'+esc(e.title)+'</div><div style="color:var(--muted);font-size:13.5px" class="ltr">'+esc([e.date||"?",e.time,e.venue,e.city,(e.country||"").toUpperCase(),e.price,e.type].filter(function(x){return x&&x!=="unknown";}).join(" · "))+'</div>'+(e.notes?'<div class="sm">'+esc(e.notes)+'</div>':'')+(u?'<div class="sm"><a href="'+esc(u)+'" target="_blank" rel="noopener">لینک</a></div>':'')+(ok?'':'<div class="sm">تاریخ یا کشور نامشخص؛ فقط رد کردن ممکن است.</div>')+'</div><div class="act"><button class="btn small ok" data-pact="approve" data-id="'+esc(e.id)+'"'+(ok?'':' disabled')+'>تأیید و انتشار</button><button class="btn small no" data-pact="reject" data-id="'+esc(e.id)+'">رد</button></div></div>';
  }).join("") : '<div class="empty">پیشنهاد جدیدی نیست.</div>';
}
function eventsOnDay(day,code){
  return st.events.filter(function(e){
    if(!isUpcoming(e)||(e.ongoing&&!e.endDate)) return false;
    if(code!=="all" && e.country!==code) return false;
    return e.date<=day && (e.endDate||e.date)>=day;
  }).length;
}
function renderCal(){
  var el=$("calStrip"); if(!el) return;
  var c=st.country, small=c!=="all", html="", totalC=0, t0=todayISO();
  for(var i=0;i<14;i++){
    var d=new Date(); d.setDate(d.getDate()+i); var k=iso(d);
    var n=eventsOnDay(k,c), all=small?eventsOnDay(k,"all"):n; totalC+=n;
    html+='<button type="button" class="calday'+(n?' has':'')+(st.day===k?' on':'')+(i===0?' today':'')+'" role="listitem" data-day="'+k+'" aria-pressed="'+(st.day===k)+'" aria-label="'+esc(fmt(d,{weekday:"long",day:"numeric",month:"long"}))+': '+n+'">'+
      '<span class="cw">'+(i===0?"امروز":esc(fmt(d,{weekday:"short"})))+'</span>'+
      '<span class="cd">'+esc(fmt(d,{day:"numeric"}))+'</span>'+
      '<span class="cm">'+EN_MONTHS[d.getMonth()]+'</span>'+
      '<span class="cn">'+(n? faNum(n):'·')+'</span>'+
      (small?'<span class="ca" title="کل اروپا">'+(all?faNum(all):'')+'</span>':'')+
      '</button>';
  }
  el.innerHTML=html;
  $("calSub").textContent=(small?(NAME[c]+" · "):"")+faNum(totalC)+" برنامه"+(small?" · عدد ریز: کل اروپا":"")+(st.day?" · فیلتر روز فعال (دوباره بزن تا برداشته شود)":"");
}
$("calStrip").addEventListener("click",function(ev){
  var b=ev.target.closest("button[data-day]"); if(!b) return;
  var k=b.getAttribute("data-day"); st.day=(st.day===k?"":k); if(st.day) st.when="all";
  render(); goEvents();
});
// ---------- flag-colour tint in the hero (per selected country) ----------
// seg = [colour, from%, to%]; colour "-" = transparent. White is drawn at half strength so the white text stays readable.
function L(angle,segs){ return "linear-gradient("+angle+"deg,"+segs.map(function(g){ var c=g[0]==="-"?"transparent":(g[0]==="#FFFFFF"?"#FFFFFF80":g[0]); return c+" "+g[1]+"% "+g[2]+"%"; }).join(",")+")"; }
function bands(angle,cols,w){ var n=cols.length, x=0; return [L(angle,cols.map(function(c,i){ var f=x, t=(w?x+w[i]:(i+1)*100/n); x=t; return [c,f,t]; }))]; }
// Nordic-style cross: a vertical and a horizontal bar over a field. inner = optional second (inner) colour.
function cross(field,bar,inner,vx,hx){ // vx/hx = [from,to] of the bar
  var bV=[L(90,[["-",0,vx[0]],[bar,vx[0],vx[1]],["-",vx[1],100]])], bH=[L(180,[["-",0,hx[0]],[bar,hx[0],hx[1]],["-",hx[1],100]])], out=bH.concat(bV);
  if(inner){ var m=function(r,k){ return [r[0]+(r[1]-r[0])*k, r[1]-(r[1]-r[0])*k]; }, iv=m(vx,.3), ih=m(hx,.3);
    out=[L(180,[["-",0,ih[0]],[inner,ih[0],ih[1]],["-",ih[1],100]]),L(90,[["-",0,iv[0]],[inner,iv[0],iv[1]],["-",iv[1],100]])].concat(out); }
  return out.concat([L(90,[[field,0,100]])]);
}
var TINT={
  se:cross("#006AA7","#FECC00",null,[28,44],[38,62]),
  no:cross("#BA0C2F","#FFFFFF","#00205B",[26,46],[36,64]),
  dk:cross("#C8102E","#FFFFFF",null,[28,44],[38,62]),
  fi:cross("#FFFFFF","#003580",null,[28,44],[38,62]),
  gb:cross("#012169","#FFFFFF","#C8102E",[38,62],[36,64]),
  ch:cross("#D52B1E","#FFFFFF",null,[42,58],[34,66]),
  de:bands(180,["#000000","#DD0000","#FFCE00"]),
  nl:bands(180,["#AE1C28","#FFFFFF","#21468B"]),
  at:bands(180,["#ED2939","#FFFFFF","#ED2939"]),
  hu:bands(180,["#CE2939","#FFFFFF","#477050"]),
  lu:bands(180,["#EF3340","#FFFFFF","#00A3E0"]),
  es:bands(180,["#AA151B","#F1BF00","#AA151B"],[25,50,25]),
  gr:bands(180,["#0D5EAF","#FFFFFF","#0D5EAF","#FFFFFF","#0D5EAF","#FFFFFF","#0D5EAF","#FFFFFF","#0D5EAF"]),
  pl:bands(180,["#FFFFFF","#DC143C"]),
  fr:bands(90,["#0055A4","#FFFFFF","#EF4135"]),
  it:bands(90,["#009246","#FFFFFF","#CE2B37"]),
  be:bands(90,["#000000","#FAE042","#ED2939"]),
  ie:bands(90,["#169B62","#FFFFFF","#FF883E"]),
  pt:bands(90,["#006600","#FF0000"],[40,60]),
  cz:[L(90,[["#11457E",0,30],["-",30,100]])].concat(bands(180,["#FFFFFF","#D7141A"])),
  tr:["radial-gradient(circle at 38% 50%,#FFFFFFB0 0 13%,transparent 14%)",L(90,[["#E30A17",0,100]])]
};
var tintShown=null;
function renderTint(){
  var el=$("flagTint"); if(!el||tintShown===st.country) return; tintShown=st.country;
  var t=TINT[st.country];
  el.style.opacity="0";
  el.parentNode.classList.toggle("tinted",!!t);
  if(!t) return;
  setTimeout(function(){ el.style.background=t.join(","); el.style.opacity=".8"; },60);
}
function renderRemoved(){
  var el=$("removed"); if(!el||!st.canEdit) return;
  var list=st.sources.filter(function(s){return s.status==="rejected";}).sort(function(a,b){return String(b.reviewedAt||"").localeCompare(String(a.reviewedAt||""));});
  $("removedN").textContent="("+faNum(list.length)+")";
  if(!list.length){ el.innerHTML='<li>صفحه‌ی حذف‌شده‌ای نیست.</li>'; return; }
  el.innerHTML=list.map(function(s){
    var u=safeUrl(s.url), d=s.reviewedAt?String(s.reviewedAt).slice(0,10):"";
    return '<li class="srcli"><div class="srctop"><span class="ltr" style="text-align:left"><span class="pf">'+esc(s.platform)+'</span><br>'+(u?'<a href="'+esc(u)+'" target="_blank" rel="noopener">'+esc(s.handle)+'</a>':esc(s.handle))+'</span><span style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;color:var(--muted)">'+esc((s.city?s.city+"، ":"")+(s.country==="all"?"همه کشورها":(NAME[s.country]||s.country||"")))+(d?' · <span class="ltr">'+esc(d)+'</span>':'')+'<button class="btn small ghost" data-restore="'+esc(s.id)+'">بازگرداندن</button></span></div></li>';
  }).join("");
}
function renderFilters(){ renderCats(); renderCities(); renderNav(); renderList(); }
function render(){ renderGroups(); renderCountrySelect(); renderFlags(); renderHero(); renderRail(); renderFilters(); renderFoot(); renderSources(); renderMine(); renderQueue(); renderPending(); renderCal(); renderTint(); renderRemoved(); }

// ---------- city skyline in the hero ----------
(function(){
  var g=$("skyline"), x=0, h="", seed=7;
  function rnd(){ seed=(seed*9301+49297)%233280; return seed/233280; }
  while(x<1440){
    var w=40+rnd()*70, hh=90+rnd()*230, y=520-hh;
    h+='<rect x="'+x.toFixed(0)+'" y="'+y.toFixed(0)+'" width="'+w.toFixed(0)+'" height="'+hh.toFixed(0)+'"/>';
    if(rnd()>.75) h+='<rect x="'+(x+w/2-2).toFixed(0)+'" y="'+(y-30).toFixed(0)+'" width="4" height="30"/>';
    for(var wy=y+14; wy<500; wy+=18){ for(var wx=x+8; wx<x+w-8; wx+=14){ if(rnd()>.72) h+='<rect x="'+wx.toFixed(0)+'" y="'+wy.toFixed(0)+'" width="5" height="7" fill="#F2C46B" opacity="'+(.35+rnd()*.5).toFixed(2)+'"/>'; } }
    x+=w+2+rnd()*6;
  }
  g.innerHTML=h;
})();

// ---------- interactions ----------
function setCountry(c){
  st.country=NAME[c]?c:"all"; st.city="";
  try{ if(st.country==="all") history.replaceState(null,"",location.pathname+location.search); else location.hash=st.country; }catch(e){}
  render();
}
function fromHash(){ var h=(location.hash||"").slice(1).toLowerCase(); if(NAME[h]&&h!=="all"){ st.country=h; return true; } if(!h||h==="all"){ st.country="all"; } return false; }
window.addEventListener("hashchange",function(){ if(fromHash()){ st.city=""; render(); } });
fromHash();

function goEvents(){ var el=$("events"); if(el) el.scrollIntoView({block:"start"}); }
$("flags").addEventListener("click",function(ev){ var b=ev.target.closest(".flag"); if(!b) return; setCountry(b.getAttribute("data-c")); });
$("countrySel").addEventListener("change",function(){ setCountry(this.value); });
$("nav").addEventListener("click",function(ev){
  var b=ev.target.closest("button[data-nav]"); if(!b) return;
  var k=b.getAttribute("data-nav");
  if(k==="add"){ $("add").scrollIntoView({block:"start"}); return; }
  if(k==="groups"){ $("groups").scrollIntoView({block:"start"}); return; }
  if(["new","today","weekend"].indexOf(k)>=0){ st.when=(st.when===k?"all":k); }
  else if(["concert","party","celebration","culture"].indexOf(k)>=0){ st.cat=(st.cat===k?"all":k); }
  else { st.type=(st.type===k?"all":k); }
  renderFilters(); goEvents();
});
$("cats").addEventListener("click",function(ev){ var b=ev.target.closest(".chip"); if(!b) return; st.cat=b.getAttribute("data-cat"); renderFilters(); });
$("typeSel").addEventListener("change",function(){ st.type=this.value; renderFilters(); });
$("whenSel").addEventListener("change",function(){ st.when=this.value; renderFilters(); });
$("city").addEventListener("change",function(){ st.city=this.value; renderList(); });
var qt; $("q").addEventListener("input",function(){ var v=this.value; clearTimeout(qt); qt=setTimeout(function(){ st.q=v.trim().toLowerCase(); $("hq").value=v; renderList(); },150); });
$("heroSearch").addEventListener("submit",function(ev){ ev.preventDefault(); var v=$("hq").value; st.q=v.trim().toLowerCase(); $("q").value=v; renderFilters(); goEvents(); });
$("weekendCard").addEventListener("click",function(){ st.when="weekend"; renderFilters(); goEvents(); });
$("fab").addEventListener("click",function(){ st.when="today"; renderFilters(); goEvents(); });
document.querySelectorAll("[data-goto]").forEach(function(b){ b.addEventListener("click",function(){ st.when="all"; st.cat="all"; renderFilters(); goEvents(); }); });
function step(){ var r=$("rail"); var c=r.firstElementChild; return c? c.getBoundingClientRect().width+18 : 300; }
// RTL: the "next" card is to the left, so scroll by a negative amount.
$("next").addEventListener("click",function(){ $("rail").scrollBy({left:-step(),behavior:"smooth"}); });
$("prev").addEventListener("click",function(){ $("rail").scrollBy({left:step(),behavior:"smooth"}); });
function updateArrows(){ var r=$("rail"), max=r.scrollWidth-r.clientWidth, x=Math.abs(r.scrollLeft); $("prev").disabled=x<4; $("next").disabled=x>=max-4; }
$("rail").addEventListener("scroll",function(){ updateArrows(); },{passive:true});
window.addEventListener("resize",updateArrows);

(function(){ $("sCountry").innerHTML='<option value="all">همه کشورها · Any country (دی‌جی/برگزارکننده در چند کشور)</option>'+COUNTRIES.filter(function(c){return c[0]!=="all";}).map(function(c){return '<option value="'+c[0]+'">'+esc(c[1]+" · "+c[2])+'</option>';}).join(""); })();

// ---------- open links only on click (no visible links / no href on public cards) ----------
function goLink(el){ var u=safeUrl(el.getAttribute("data-go")); if(u) window.open(u,"_blank","noopener"); }
document.addEventListener("click",function(ev){ var c=ev.target.closest("[data-go]"); if(c) goLink(c); });
document.addEventListener("keydown",function(ev){ if(ev.key!=="Enter"&&ev.key!==" ") return; var c=ev.target.closest&&ev.target.closest("[data-go]"); if(c&&ev.target===c){ ev.preventDefault(); goLink(c); } });

// ---------- submit ----------
function normUrl(p,h){
  h=h.trim(); if(/^https?:\/\//i.test(h)) return h;
  var x=h.replace(/^@/,"");
  if(p==="instagram") return "https://www.instagram.com/"+encodeURIComponent(x)+"/";
  if(p==="facebook") return "https://www.facebook.com/"+encodeURIComponent(x);
  if(p==="whatsapp") return "https://chat.whatsapp.com/"+encodeURIComponent(x);
  if(p==="telegram") return "https://t.me/"+encodeURIComponent(x);
  return "https://"+x;
}
$("sPlatform").addEventListener("change",function(){ var PH={whatsapp:"https://chat.whatsapp.com/… or https://whatsapp.com/channel/…",telegram:"@channel or https://t.me/channel",facebook:"https://facebook.com/pagename",website:"https://example.com"}; $("sHandle").placeholder=PH[this.value]||"@organizer or https://instagram.com/organizer"; });
$("subForm").addEventListener("submit",async function(ev){
  ev.preventDefault();
  var msg=$("sMsg"), fb=$("sFallback");
  msg.className="msg"; fb.hidden=true;
  var p=$("sPlatform").value, h=$("sHandle").value.trim();
  if(h.length<2){ msg.className="msg bad"; msg.textContent="آیدی یا لینک صفحه را وارد کن."; return; }
  if(p==="whatsapp" && !/^https?:\/\/(chat\.whatsapp\.com|wa\.me|(www\.)?whatsapp\.com)\//i.test(h)){ msg.className="msg bad"; msg.textContent="لینک دعوت گروه یا لینک کانال واتس‌اپ را وارد کن (مثلاً https://chat.whatsapp.com/…). شماره‌ی شخصی نفرست."; return; }
  var iconFile=$("sIcon").files&&$("sIcon").files[0];
  if(!iconFile){ msg.className="msg bad"; msg.textContent="لطفاً یک عکس برای آیکون صفحه یا گروه انتخاب کن (PNG، JPEG، GIF یا TIFF)."; return; }
  var iext=(iconFile.name.split(".").pop()||"").toLowerCase();
  if(["png","jpg","jpeg","gif","tif","tiff"].indexOf(iext)<0){ msg.className="msg bad"; msg.textContent="پسوند قابل پشتیبانی نیست. فقط PNG، JPEG، GIF یا TIFF قابل قبول است."; return; }
  if(iconFile.size>2*1024*1024){ msg.className="msg bad"; msg.textContent="حجم عکس بیشتر از ۲ مگابایت است."; return; }
  var it={key:keyFor(p,h),platform:p,handle:h,url:normUrl(p,h),country:$("sCountry").value,city:$("sCity").value.trim().slice(0,80),type:$("sType").value,kind:$("sKind").value,note:$("sNote").value.trim().slice(0,240),at:new Date().toISOString()};
  if(st.db&&st.db.checkFeed){
    var sb=$("sBtn"); sb.disabled=true; msg.className="msg"; msg.textContent="در حال بررسی…";
    var chk=null; try{ chk=await st.db.checkFeed(p,h); }catch(e){}
    sb.disabled=false;
    if(chk&&chk.exists){
      var TXT={tracked:"این صفحه همین الان هر روز پیگیری می‌شود؛ نیازی به ارسال دوباره نیست ✓",group:"این صفحه قبلاً در بخش «گروه‌ها و انجمن‌ها» هست ✓",pending:"این صفحه قبلاً فرستاده شده و منتظر بررسی ادمین است.",declined:"این صفحه قبلاً بررسی شده و به لیست پیگیری اضافه نشده."};
      msg.className="msg good"; msg.textContent=TXT[chk.status]||TXT.tracked; return;
    }
    msg.textContent="";
  }
  if(st.canEdit && decided(it.key)){ msg.className="msg good"; msg.textContent=decided(it.key).status==="approved"?"این صفحه همین الان هم پیگیری می‌شود.":"این صفحه قبلاً بررسی شده و اضافه نشده."; return; }
  if(st.db&&st.uid&&st.canWrite!==false){
    var btn=$("sBtn"); btn.disabled=true;
    try{
      if(st.db.uploadIcon){
        msg.className="msg"; msg.textContent="در حال ارسال عکس…";
        try{ var up=await st.db.uploadIcon(iconFile); it.icon=up.icon; }
        catch(ue){
          btn.disabled=false; msg.className="msg bad";
          msg.textContent=ue&&ue.status===415?"پسوند قابل پشتیبانی نیست یا فایل عکس معتبر نیست. فقط PNG، JPEG، GIF یا TIFF.":ue&&ue.status===413?"حجم عکس بیشتر از حد مجاز است.":ue&&ue.status===429?"تعداد ارسال زیاد بود؛ کمی بعد دوباره امتحان کن.":"ارسال عکس انجام نشد؛ دوباره امتحان کن.";
          return;
        }
      }
      var ref=st.db.doc("submissions/"+st.uid);
      var snap=await ref.get(); var items=(snap.exists&&snap.data().items)?snap.data().items.slice():[];
      if(!items.some(function(x){return x.key===it.key;})){
        items.push(it); if(items.length>200) items=items.slice(-200);
        await ref.set({items:items,updatedAt:it.at});
      }
      msg.className="msg good"; msg.textContent="ممنون! بعد از بررسی ادمین، این صفحه هر روز بررسی می‌شود.";
      $("sHandle").value=""; $("sNote").value=""; $("sIcon").value="";
      btn.disabled=false; return;
    }catch(e){
      btn.disabled=false;
      if(e&&e.code==="invalid_argument"){ st.canWrite=false; }
      else if(e&&e.code==="quota_exceeded"){ msg.className="msg bad"; msg.textContent="فعلاً ظرفیت پر است، کمی بعد دوباره امتحان کن."; return; }
    }
  }
  var txt="Please track this page for Kurdish & Persian events:\nPlatform: "+it.platform+"\nPage: "+it.handle+"\nLink: "+it.url+"\nCountry: "+(EN[it.country]||"Any")+(it.city?"\nCity: "+it.city:"")+"\nType: "+it.type+(it.note?"\nNote: "+it.note:"");
  fb.hidden=false;
  fb.innerHTML='<p class="sub" style="margin:4px 0 8px">حساب تو نمی‌تواند مستقیم از این صفحه ارسال کند. متن زیر را کپی کن و برای ادمین بفرست'+(st.contact?': <b class="ltr">'+esc(st.contact)+'</b>':'')+'.</p><pre class="copy">'+esc(txt)+'</pre><button type="button" class="btn ghost small" id="copyBtn" style="margin-top:8px">کپی متن</button>';
  $("copyBtn").addEventListener("click",function(){var b=this;navigator.clipboard.writeText(txt).then(function(){b.textContent="کپی شد";},function(){b.textContent="متن بالا را انتخاب و کپی کن";});});
});

// ---------- admin actions ----------
$("queue").addEventListener("click",async function(ev){
  var b=ev.target.closest("button[data-act]"); if(!b||!st.db) return;
  var k=b.getAttribute("data-k"), it=null;
  st.subs.forEach(function(d){(d.items||[]).forEach(function(x){if(x.key===k)it=x;});});
  if(!it) return;
  b.disabled=true;
  try{
    var act=b.getAttribute("data-act");
    var src={platform:it.platform,handle:it.handle,url:it.url,country:it.country,city:it.city||"",type:it.type,note:it.note||"",icon:safeIcon(it.icon),status:act==="reject"?"rejected":"approved",reviewedAt:new Date().toISOString()};
    await st.db.doc("sources/"+k).set(src);
    if(act==="approve-group"){ src.id=k; await addToGroups(src); }
  }
  catch(e){ b.disabled=false; b.textContent="خطا، دوباره"; }
});
$("pending").addEventListener("click",async function(ev){
  var b=ev.target.closest("button[data-pact]"); if(!b||!st.db) return;
  var id=b.getAttribute("data-id"), e=st.pending.find(function(x){return x.id===id;}); if(!e) return;
  b.disabled=true;
  try{
    if(b.getAttribute("data-pact")==="approve"){
      var d={title:e.title,date:e.date,endDate:e.endDate||"",ongoing:!!e.ongoing,time:e.time||"",venue:e.venue||"",city:e.city||"",country:String(e.country).toLowerCase(),price:e.price||"See ticket page",type:e.type||"both",url:e.url||"",source:e.source||"",organizer:e.organizer||"",addedAt:new Date().toISOString().slice(0,10)};
      if(safeImg(e.image)) d.image=e.image;
      if(/^\d{4}-\d{2}-\d{2}$/.test(e.endDate||"")) d.endDate=e.endDate;
      if(e.ongoing) d.ongoing=true;
      if(e.dateLabel) d.dateLabel=e.dateLabel;
      await st.db.doc("events/"+id).set(d);
      await st.db.doc("pending/"+id).update({status:"approved",reviewedAt:new Date().toISOString()});
    } else await st.db.doc("pending/"+id).update({status:"rejected",reviewedAt:new Date().toISOString()});
  }catch(err){ b.disabled=false; b.textContent="خطا، دوباره"; }
});
$("sources").addEventListener("click",async function(ev){
  var o=ev.target.closest("button[data-open]");
  if(o){ var id0=o.getAttribute("data-open"); st.openSrc=(st.openSrc===id0?null:id0); renderSources(); return; }
  var ag=ev.target.closest("button[data-addgroup]");
  if(ag&&st.db){ var s0=st.sources.find(function(x){return x.id===ag.getAttribute("data-addgroup");}); ag.disabled=true; try{ await addToGroups(s0); }catch(e){ ag.disabled=false; ag.textContent="خطا، دوباره"; } return; }
  var b=ev.target.closest("button[data-stop]"); if(!b||!st.db) return;
  b.disabled=true;
  try{ await st.db.doc("sources/"+b.getAttribute("data-stop")).update({status:"rejected",reviewedAt:new Date().toISOString()}); }catch(e){ b.disabled=false; b.textContent="خطا، دوباره"; }
});
$("removed").addEventListener("click",async function(ev){
  var b=ev.target.closest("button[data-restore]"); if(!b||!st.db) return;
  b.disabled=true;
  try{ await st.db.doc("sources/"+b.getAttribute("data-restore")).update({status:"approved",reviewedAt:new Date().toISOString()}); }catch(e){ b.disabled=false; b.textContent="خطا، دوباره"; }
});
$("contactForm").addEventListener("submit",async function(ev){
  ev.preventDefault(); if(!st.db) return;
  var v=$("contact").value.trim().slice(0,120);
  if(v===st.contact) return;
  try{ await st.db.doc("config/site").set({contact:v}); }catch(e){}
});

// ---------- data ----------
render();
(async function(){
  var timer=setTimeout(function(){ if(!st.loaded){ st.loaded=true; render(); $("list").insertAdjacentHTML("afterbegin",'<div class="note" style="margin-bottom:12px">فعلاً اتصال برقرار نیست؛ به محض برقراری، برنامه‌ها نمایش داده می‌شوند.</div>'); } },10000);
  var db=null, user=null;
  try{ db=await window.claude.use("db"); }catch(e){}
  try{ user=await window.claude.use("user"); }catch(e){}
  st.db=db;
  if(user){
    try{ st.uid=await user.id(); }catch(e){}
    try{ st.canEdit=!!(await user.canEdit()); }catch(e){}
    try{ var w=await user.can("data.write"); st.canWrite=(w==null?null:!!w); }catch(e){}
  }
  if(!db){ clearTimeout(timer); st.loaded=true; render(); return; }
  var first={ev:false,src:false};
  function maybe(){ if(first.ev&&first.src){ clearTimeout(timer); st.loaded=true; } render(); }
  db.collection("events").onSnapshot(function(snap){
    st.events=snap.docs.map(function(d){var x=Object.assign({},d.data());x.id=d.id;return x;}); first.ev=true; maybe();
  },function(){ first.ev=true; maybe(); });
  db.collection("sources").onSnapshot(function(snap){
    st.sources=snap.docs.map(function(d){var x=Object.assign({},d.data());x.id=d.id;return x;}); first.src=true; maybe();
  },function(){ first.src=true; maybe(); });
  db.collection("pending").onSnapshot(function(snap){
    st.pending=snap.docs.map(function(d){var x=Object.assign({},d.data());x.id=d.id;return x;}); renderPending();
  },function(){});
  db.collection("communities").onSnapshot(function(snap){
    st.groups=snap.docs.map(function(d){var x=Object.assign({},d.data());x.id=d.id;return x;}); renderGroups();
  },function(){});
  db.collection("submissions").onSnapshot(function(snap){
    st.subs=snap.docs.map(function(d){var x=Object.assign({},d.data());x.id=d.id;return x;}); renderMine(); renderQueue();
  },function(){});
  db.doc("config/site").onSnapshot(function(d){ st.contact=d.exists?(d.data().contact||""):""; renderQueue(); },function(){});
})();
})();
