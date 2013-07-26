//ThinkTemplate 用js实现了ThinkPHP的模板引擎。
//用户可以在手机客户端中用ThinkPHP的模板引擎。
//@author luofei614<http://weibo.com/luofei614>
//
var ThinkTemplate={
    tags:['Include','Volist','Foreach','For','Empty','Notempty','Present','Notpresent','Compare','If','Elseif','Else','Swith','Case','Default','Var','Range'],
	parse:function(tplContent,vars){
	var render=function(){
		tplContent='<% var key,mod=0;%>'+tplContent;//定义模板中循环需要使用的到变量	
        $.each(ThinkTemplate.tags,function(k,v){
            tplContent=ThinkTemplate['parse'+v](tplContent);
        });  
		return ThinkTemplate.template(tplContent,vars);
		};
		
		return render();
	},
	//解析 <% %> 标签
	template:function(text,vars){
		var source="";
		var index=0;
		var escapes = {
			"'":      "'",
			'\\':     '\\',
			'\r':     'r',
			'\n':     'n',
			'\t':     't',
			'\u2028': 'u2028',
			'\u2029': 'u2029'
		};
		var escaper = /\\|'|\r|\n|\t|\u2028|\u2029/g;
		text.replace(/<%=([\s\S]+?)%>|<%([\s\S]+?)%>/g,function(match,interpolate,evaluate,offset){
			var p=text.slice(index,offset).replace(escaper,function(match){
				return '\\'+escapes[match];
			});
			if(''!=$.trim(p)){
				source+="__p+='"+p+"';\n";	
			}

			if(evaluate){
				source+=evaluate+"\n";
			}	
			if(interpolate){
				source+="if( 'undefined'!=typeof("+interpolate+") && (__t=(" + interpolate + "))!=null) __p+=__t;\n";
			}
			index=offset+match.length;
			return match;
		});
		source+="__p+='"+text.slice(index).replace(escaper,function(match){ return '\\'+escapes[match]; })+"';\n";//拼接剩余的字符串

		source = "var __t,__p='',__j=Array.prototype.join," +
			"print=function(){__p+=__j.call(arguments,'');};\n" +
			"with(obj){\n"+
			source + 
			"}\n"+
			"return __p;\n";
		try {
			render = new Function('obj', source);

		} catch (e) {
			e.source = source;
			throw e;
		}
		return render(vars);
	},
	parseVar:function(tplContent){
		var matcher=/\{\$(.*?)\}/g
			return tplContent.replace(matcher,function(match,varname,offset){
				//支持定义默认值
				if(varname.indexOf('|')!=-1){
					var arr=varname.split('|');
					var name=arr[0];
					var defaultvalue='""';
					arr[1].replace(/default=(.*?)$/ig,function(m,v,o){
						defaultvalue=v;
					});
					return '<% '+name+'?print('+name+'):print('+defaultvalue+');  %>';
				}
				return '<%='+varname+'%>';
			});	
	},
    //include标签解析 路径需要写全，写为 Action:method, 暂不支持变量。 
    parseInclude:function(tplContent){
		var include=/<include (.*?)\/?>/ig;
        tplContent=tplContent.replace(include,function(m,v,o){
            var $think=$('<think '+v+' />');
            var file=$think.attr('file').replace(':','/')+'.html';
            var content='';
            //加载模板
            $.ajax({
                dataType:'text',
                url:file,
                cache:false,
                async:false,//同步请求
                success:function(d,s,x){
                    content=d;
                },
                error:function(){
                    //pass
                }
            });
            return content;
        });
        tplContent=tplContent.replace('</include>','');//兼容浏览器中元素自动闭合的情况
        return tplContent;
    },
	//volist标签解析
	parseVolist:function(tplContent){
		var voliststart=/<volist (.*?)>/ig;
		var volistend=/<\/volist>/ig;
		//解析volist开始标签
		tplContent=tplContent.replace(voliststart,function(m,v,o){
			//属性分析
			var $think=$('<think '+v+' />');
			var name=$think.attr('name');
			var id=$think.attr('id');
			var empty=$think.attr('empty')||'';
			var key=$think.attr('key')||'i';	
			var mod=$think.attr('mod')||'2';
			//替换为代码
			return '<% if("undefined"==typeof('+name+') || ThinkTemplate.empty('+name+')){'+
				' print(\''+empty+'\');'+
			' }else{ '+
				key+'=0;'+
			' $.each('+name+',function(key,'+id+'){'+
				' mod='+key+'%'+mod+';'+
				' ++'+key+';'+
				' %>';
			});
		//解析volist结束标签
		tplContent=tplContent.replace(volistend,'<% }); } %>');
		return tplContent;
	},
	//解析foreach标签
	parseForeach:function(tplContent){
		var foreachstart=/<foreach (.*?)>/ig;
		var foreachend=/<\/foreach>/i;	
		tplContent=tplContent.replace(foreachstart,function(m,v,o){
			var $think=$('<think '+v+' />');	
			var name=$think.attr('name');
			var item=$think.attr('item');
			var key=$think.attr('key')||'key';
			return '<% $.each('+name+',function('+key+','+item+'){  %>'
			});
			tplContent=tplContent.replace(foreachend,'<% }); %>');
		return tplContent;
	},
	parseFor:function(tplContent){
		var forstart=/<for (.*?)>/ig;
		var forend=/<\/for>/ig;
		tplContent=tplContent.replace(forstart,function(m,v,o){
			var $think=$('<think '+v+' />');	
			var name=$think.attr('name') || 'i';
			var comparison=$think.attr('comparison') || 'lt';
			var start=$think.attr('start') || '0';
			if('$'==start.substr(0,1)){
				start=start.substr(1);
			}
			var end=$think.attr('end') || '0';
			if('$'==end.substr(0,1)){
				end=end.substr(1);
			}
			var step=$think.attr('step') || '1';
			if('$'==step.substr(0,1)){
				step=step.substr(1);	
			}
			return '<% for(var '+name+'='+start+';'+ThinkTemplate.parseCondition(name+comparison+end)+';i=i+'+step+'){  %>'
			});
		tplContent=tplContent.replace(forend,'<% } %>');
		return tplContent;
	},
	//empty标签
	parseEmpty:function(tplContent){
		var	emptystart=/<empty (.*?)>/ig;
		var emptyend=/<\/empty>/ig;
		tplContent=tplContent.replace(emptystart,function(m,v,o){
			var name=$('<think '+v+' />').attr('name');
			return '<% if("undefined"==typeof('+name+') || ThinkTemplate.empty('+name+')){ %>';
			});
		tplContent=tplContent.replace(emptyend,'<% } %>');
		return tplContent;
	},
	//notempty 标签解析
	parseNotempty:function(tplContent){
		var	notemptystart=/<notempty (.*?)>/ig;
		var notemptyend=/<\/notempty>/ig;
		tplContent=tplContent.replace(notemptystart,function(m,v,o){
			var name=$('<think '+v+' />').attr('name');
			return '<% if("undefined"!=typeof('+name+') && !ThinkTemplate.empty('+name+')){ %>';
			});
		tplContent=tplContent.replace(notemptyend,'<% } %>');
		return tplContent;
	},
	//present标签解析
	parsePresent:function(tplContent){
		var	presentstart=/<present (.*?)>/ig;
		var presentend=/<\/present>/ig;
		tplContent=tplContent.replace(presentstart,function(m,v,o){
			var name=$('<think '+v+' />').attr('name');
			return '<% if("undefined"!=typeof('+name+')){ %>';
			});
		tplContent=tplContent.replace(presentend,'<% } %>');
		return tplContent;
	},
	//notpresent 标签解析
	parseNotpresent:function(tplContent){
		var	notpresentstart=/<notpresent (.*?)>/ig;
		var notpresentend=/<\/notpresent>/ig;
		tplContent=tplContent.replace(notpresentstart,function(m,v,o){
			var name=$('<think '+v+' />').attr('name');
			return '<% if("undefined"==typeof('+name+')){ %>';
			});
		tplContent=tplContent.replace(notpresentend,'<% } %>');
		return tplContent;
	},
	parseCompare:function(tplContent){
		var compares={
			"compare":"==",
			"eq":"==",
			"neq":"!=",
			"heq":"===",
			"nheq":"!==",
			"egt":">=",
			"gt":">",
			"elt":"<=",
			"lt":"<"
		};	
		$.each(compares,function(type,sign){
			var start=new RegExp('<'+type+' (.*?)>','ig');
			var end=new RegExp('</'+type+'>','ig');
			tplContent=tplContent.replace(start,function(m,v,o){
				var	$think=$('<think '+v+' />');
				var name=$think.attr('name');
				var value=$think.attr('value');
				if("compare"==type && $think.attr('type')){
					sign=compares[$think.attr('type')];
				}
				if('$'==value.substr(0,1)){
					//value支持变量
					value=value.substr(1);	
				}else{
					value='"'+value+'"';
				}
				return '<% if('+name+sign+value+'){  %>';
				});
			tplContent=tplContent.replace(end,'<% } %>');

		});
		return tplContent;
	},
	//解析if标签
	parseIf:function(tplContent){
		var ifstart=/<if (.*?)>/ig;
		var ifend=/<\/if>/ig;
		tplContent=tplContent.replace(ifstart,function(m,v,o){
			var condition=$('<think '+v+' />').attr('condition');	
			return '<% if('+ThinkTemplate.parseCondition(condition)+'){ %>';
			});
		tplContent=tplContent.replace(ifend,'<% } %>');
		return tplContent;
	},
	//解析elseif
	parseElseif:function(tplContent){
		var elseif=/<elseif (.*?)\/?>/ig;
		tplContent=tplContent.replace(elseif,function(m,v,o){
			var condition=$('<think '+v+'  />').attr('condition');
			return '<% }else if('+ThinkTemplate.parseCondition(condition)+'){ %>';
			});
        tplContent=tplContent.replace('</elseif>','');
		return tplContent;
	},
	//解析else标签
	parseElse:function(tplContent){
		    var el=/<else\s*\/?>/ig	
			tplContent=tplContent.replace(el,'<% }else{ %>');
            tplContent=tplContent.replace('</else>','');
            return tplContent;
			},
	//解析swith标签
	parseSwith:function(tplContent){
		var switchstart=/<switch (.*?)>(\s*)/ig;	
		var switchend=/<\/switch>/ig;
		tplContent=tplContent.replace(switchstart,function(m,v,s,o){
			var name=$('<think '+v+' >').attr('name');	
			return '<% switch('+name+'){ %>';
			});
		tplContent=tplContent.replace(switchend,'<% } %>');
		return tplContent;
	},
	//解析case标签
	parseCase:function(tplContent){
		var casestart=/<case (.*?)>/ig;	
		var caseend=/<\/case>/ig;
		var breakstr='';
		tplContent=tplContent.replace(casestart,function(m,v,o){
			var $think=$('<think '+v+'  />');
			var value=$think.attr('value');
			if('$'==value.substr(0,1)){
				value=value.substr(1);
			}else{
				value='"'+value+'"';
			}
			if('false'!=$think.attr('break')){
				breakstr='<% break; %> ';
			}
			return '<% case '+value+':  %>';
		});
		tplContent=tplContent.replace(caseend,breakstr);
		return tplContent;
	},
	//解析default标签
	parseDefault:function(tplContent){
		var defaulttag=/<default\s*\/?>/ig;	
		tplContent=tplContent.replace(defaulttag,'<% default: %>');
        tplContent=tplContent.replace('</default>','');
		return tplContent;
	},
	//解析in,notin,between,notbetween 标签
	parseRange:function(tplContent){
		var ranges=['in','notin','between','notbetween'];
		$.each(ranges,function(k,tag){
			var start=new RegExp('<'+tag+' (.*?)>','ig');
			var end=new RegExp('</'+tag+'>','ig');
			tplContent=tplContent.replace(start,function(m,v,o){
				var	$think=$('<think '+v+' />');
				var name=$think.attr('name');
				var value=$think.attr('value');
				if('$'==value.substr(0,1)){
					value=value.substr(1);
				}else{
					value='"'+value+'"';
				}
				switch(tag){
					case "in":
						var condition='ThinkTemplate.inArray('+name+','+value+')';	
							break;
							case "notin":
							var condition='!ThinkTemplate.inArray('+name+','+value+')';	
								break;
								case "between":
								var condition=name+'>='+value+'[0] && '+name+'<='+value+'[1]';
								break;
								case "notbetween":
								var condition=name+'<'+value+'[0] || '+name+'>'+value+'[1]';
								break;
								}
								return '<% if('+condition+'){ %>'
								});
							tplContent=tplContent.replace(end,'<% } %>')
							});
						return tplContent;
	},
    //扩展
    extend:function(name,cb){
        name=name.substr(0,1).toUpperCase()+name.substr(1);
        this.tags.push(name);
        this['parse'+name]=cb;
    },
	//判断是否在数组中，支持判断object类型的数据
	inArray:function(name,value){
		if('string'==$.type(value)){
			value=value.split(',');
		}
		var ret=false;
		$.each(value,function(k,v){
			if(v==name){
				ret=true;
				return false;
			}	
		});
		return ret;
	},
	empty:function(data){
		if(!data)
			return true;
		if('array'==$.type(data) && 0==data.length)
			return true;
		if('object'==$.type(data) && 0==Object.keys(data).length)
			return true;
		return false;
	},
	parseCondition:function(condition){
		var conditions={
			"eq":"==",
			"neq":"!=",
			"heq":"===",
			"nheq":"!==",
			"egt":">=",
			"gt":">",
			"elt":"<=",
			"lt":"<",
			"or":"||",
			"and":"&&",
			"\\$":""
		};		
		$.each(conditions,function(k,v){
			var matcher=new RegExp(k,'ig');	
			condition=condition.replace(matcher,v);
		});
		return condition;
	}


};

//TPMobi框架
//实现用ThinkPHP做手机客户端
//@author luofei614<http://weibo.com/luofei614>
var TPM={
	op:{
		api_base:'',//接口基地址，末尾不带斜杠
		api_index:'/Index/index',//首页请求地址
		main:"main",//主体层的ID
		routes:{}, //路由,支持参数如:id 支持通配符*
		error_handle:false,//错误接管函数
        _before:[],
		_ready:[],//UI回调函数集合
        single:true,//单一入口模式

		ajax_wait:".ajax_wait",//正在加载层的选择符
		ajax_timeout:15000,//ajax请求超时时间
		ajax_data_type:'',//请求接口类型 如json，jsonp
		ajax_jsonp_callback:'callback',//jsonp 传递的回调函数参数名词

		before_request_api:false,//请求接口之前的hook
        //请求接口之后的hook,处理TP的success和error
		after_request_api:function(data,url){
            if(data.info){
                TPM.info(data.info,function(){
                  if(data.url){
                            TPM.http(data.url);
                        }else if(1==data.status){
                            //如果success， 刷新数据  
                            TPM.reload(TPM.op.main);
                   }
                });
                return false;
            }
        },

        anchor_move_speed:500, //移动到锚点的速度

		tpl_path_var:'_think_template_path',//接口指定模板

		tpl_parse_string:{
            '../Public':'./Public'
        },//模板替换变量

        //指定接口请求的header
		headers:{
            'client':'PhoneClient',
            //跨域请求时，不会带X-Requested-with 的header，会导致服务认为不是ajax请求，所以这样手动加上这个header 。
            'X-Requested-With':'XMLHttpRequest'
        },

		tpl:ThinkTemplate.parse//模板引擎

	},
	config:function(options){
		$.extend(this.op,options);
	},
	ready:function(fun){
		this.op._ready.push(fun);
	},
    before:function(fun){
        this.op._before.push(fun);
    },
	//输出错误
	error:function(errno,msg){
        TPM.alert('错误['+errno+']：'+msg);
	},
    info:function(msg,cb){
        if('undefined'==typeof(tpm_info)){
            alert(msg);
            if($.isFunction(cb)) cb();
        }else{
            tpm_info(msg,cb);
        }
     },
    alert:function(msg,cb,title){
        if('undefined'==typeof(tpm_alert)){
            alert(msg);
            if($.isFunction(cb)) cb();
        }else{
            tpm_alert(msg,cb,title);
        }    
    },
    //初始化运行
	run:function(options,vars){
		if(!this.defined(window.jQuery) && !this.defined(window.Zepto)){
			this.error('-1','请加载jquery或zepto');
			return ;
		}
        //如果只设置api_base 可以只传递一个字符串。
        if('string'==$.type(options)){
            options={api_base:options};
        }
		//配置处理
		options=options||{};
		this.config(options);
		$.ajaxSetup({
			error:this.ajaxError,
			timeout:this.op.ajax_timeout || 5000,
			cache:false,
			headers:this.op.headers
		});
		var _self=this;
		//ajax加载状态
        window.TPMshowAjaxWait=true;
		$(document).ajaxStart(function(){
            //在程序中可以设置TPMshowAjaxWait为false,终止显示等待层。 
			if(window.TPMshowAjaxWait) $(_self.op.ajax_wait).show();
		}
		).ajaxStop(function(){
			$(_self.op.ajax_wait).hide();
		});
		$(document).ready(function(){
            //标签解析
            vars=vars||{};
            var render=function(vars){
                var tplcontent=$('body').html();
                tplcontent=tplcontent.replace(/&lt;%/g,'<%');
                tplcontent=tplcontent.replace(/%&gt;/g,'%>');
                var html=_self.parseTpl(tplcontent,vars);
                $('body').html(html);
                if(!_self.op.single){

                    $.each(_self.op._ready,function(k,fun){
                        fun($);
                    });
               }
            }
            if('string'==$.type(vars)){
                _self.sendAjax(vars,{},'get',function(response){
                     render(response);
                });
            }else{
                render(vars);
            }

                      
            if(_self.op.single){
                //单一入口模式
                _self.initUI(document);
                var api_url=''!=location.hash?location.hash.substr(1):_self.op.api_index;
                _self.op._old_hash=location.hash;
                _self.http(api_url);	
                //监听hash变化
                var listenHashChange=function(){
                    if(location.hash!=_self.op._old_hash){
                        var api_url=''!=location.hash?location.hash.substr(1):_self.op.api_index;
                        _self.http(api_url);
                    }
                    setTimeout(listenHashChange,50);
                }
                listenHashChange();
            }
		});
	},
	//初始化界面
	initUI:function(_box){
       //调用自定义加载完成后的UI处理函数,自定义事件绑定先于系统绑定，可以控制系统绑定函数的触发。 
        var selector=function(obj){
                var $obj=$(obj,_box)
				return $obj.size()>0?$obj:$(obj);
			};
    
        $.each(this.op._before,function(k,fun){
			fun(selector);
		})

		var _self=this;
		//A标签， 以斜杠开始的地址才会监听，不然会直接打开
		$('a[href^="/"],a[href^="./"]',_box).click(function(e){
            if(false===e.result)  return ; //如果自定义事件return false了， 不再指向请求操作
			e.preventDefault();
            //如果有tpl属性，则光请求模板
            var url=$(this).attr('href');
            if(undefined!==$(this).attr('tpl')){
                url='.'+url+'.html';
            }
			//绝对地址的链接不过滤
			_self.http(url,$(this).attr('rel'));
		});
		//form标签的处理
		$('form[action^="/"],form[action^="./"]',_box).submit(function(e){
            if(false===e.result)  return ; //如果自定义事件return false了， 不再指向请求操作
			e.preventDefault();
            var url=$(this).attr('action');
            if(undefined!==$(this).attr('tpl')){
                url='.'+url+'.html';
            }
			_self.http(url,$(this).attr('rel'),$(this).serializeArray(),$(this).attr('method'));
		});
		//锚点处理
		$('a[href^="#"]',_box).click(function(e){
			e.preventDefault();
			var anchor=$(this).attr('href').substr(1);
			if($('#'+anchor).size()>0){
				_self.scrollTop($('#'+anchor),_self.op.anchor_move_speed);	
			}else if($('a[name="'+anchor+'"]').size()>0){
				_self.scrollTop($('a[name="'+anchor+'"]'),_self.op.anchor_move_speed);
			}else{
				_self.scrollTop(0,_self.op.anchor_move_speed);
			}
		});
       
        $.each(this.op._ready,function(k,fun){
			fun(selector);
		})

	},
	//请求接口， 支持情况：1, 请求接口同时渲染模板 2,只请求模板不请求接口 3,只请求接口不渲染模板， 如果有更复杂的逻辑可以自己封住函数,调TPM.sendAjax, TPM.render。
	http:function(url,rel,data,type){
		rel=rel||this.op.main;
		type=type || 'get';
		//分析url，如果./开始直接请求模板
		if('./'==url.substr(0,2)){
			this.render(url,rel);	
			$('#'+rel).data('url',url);

			if(this.op.main==rel && 'get'==type.toLowerCase()) this.changeHash(url);
			return ;
		}
		//分析模板地址
		var tpl_path=this.route(url);
		//改变hash
		if(tpl_path && this.op.main==rel && 'get'==type.toLowerCase()) this.changeHash(url);
		//ajax请求
		var _self=this;
		this.sendAjax(url,data,type,function(response){
			if(!tpl_path && _self.defined(response[_self.op.tpl_path_var])){
				tpl_path=response[_self.op.tpl_path_var]; //接口可以指定模板	
		        //改变hash
		        if(tpl_path && _self.op.main==rel && 'get'==type.toLowerCase()) _self.changeHash(url);
			}
			if(!tpl_path){
				//如果没有模板，默认只请求ajax，请求成后刷新rel
				if('false'!=rel.toLowerCase()) _self.reload(rel);
			}else{
				//模板渲染
				_self.render(tpl_path,rel,response);
				$('#'+rel).data('url',url);	
			}
		});
	},
	sendAjax:function(url,data,type,cb,async,options){
		var _self=this;
		data=data||{};
		type=type||'get';
		options=options||{};

		api_options=$.extend({},_self.op,options);
        if(false!==async){
            async==true;
        }
		//请求接口之前hook（可以用做签名）
		if($.isFunction(api_options.before_request_api)) 
			data=api_options.before_request_api(data,url);
		//ajax请求
        //TODO ,以http开头的url，不加api_base
		var api_url=api_options.api_base+url;
		
		$.ajax(
				{
				type: type,
				url: api_url,
				data: data,
				dataType:api_options.ajax_data_type||'',
				jsonp:api_options.ajax_jsonp_callback|| 'callback',
                async:async,
				success: function(d,s,x){
                       if(redirect=x.getResponseHeader('redirect')){
                           //跳转
                           if(api_options.single) _self.http(redirect);
                           return ;
                        }
						//接口数据分析
						try{
							var response='object'==$.type(d)?d:$.parseJSON(d);
						}catch(e){
							_self.error('-2','接口返回数据格式错误');
							return ;
						}
						//接口请求后的hook
						if($.isFunction(api_options.after_request_api)){
							var hook_result=api_options.after_request_api(response,url);
							if(undefined!=hook_result){
								response=hook_result;
							}
						}
						if(false!=response && $.isFunction(cb))
							cb(response); 
					}
				}
		);
	},
	changeHash:function(url){
		if(url!=this.op.api_index){
			this.op._old_hash='#'+url;
			location.hash=url;
		}else{
			if(''!=this.op._old_hash) this.op._old_hash=this.isIE()?'#':'';//IE如果描点为# 获得值不为空
			if(''!=location.hash) location.hash='';//赋值为空其实浏览器会赋值为 #
		}	
	},
	//渲染模板
	render:function(tpl_path,rel,vars){
		vars=vars||{};
		var _self=this;
		$.get(tpl_path,function(d,x,s){
			//模板解析
			var content=_self.parseTpl(d,vars);
			//解析模板替换变量
			$.each(_self.op.tpl_parse_string,function(find,replace){
				var matcher=new RegExp(find.replace(/[-[\]{}()+?.,\\^$|#\s]/g,'\\$&'),'g');	
				content=content.replace(matcher,replace);
			});
			//分离js
			var ret=_self.stripScripts(content);
			var html=ret.text;
			var js=ret.scripts;
			$('#'+rel).empty().append(html);
			_self.initUI($('#'+rel));
			//执行页面js
			_self.execScript(js,$('#'+rel));

		},'text');	
	},
	//重新加载区域内容
	reload:function(rel){
		var url=$('#'+rel).data('url');
		if(url){
			this.http(url,rel);
		}
	},
	//路由解析
	route:function(url){
		var tpl_path=false;
		var _self=this;
		$.each(this.op.routes,function(route,path){
			if(_self._routeToRegExp(route).test(url)){
				tpl_path=path;
				return false;
			}
		});
		return tpl_path;	
	},
	_routeToRegExp: function(route) {
		var namedParam    = /:\w+/g;
		var splatParam    = /\*\w+/g;
		var escapeRegExp  = /[-[\]{}()+?.,\\^$|#\s]/g;
		route = route.replace(escapeRegExp, '\\$&')
			.replace(namedParam, '([^\/]+)')
			.replace(splatParam, '(.*?)');
		return new RegExp('^' + route + '$');
	},
	//模板解析
	parseTpl:function(tplContent,vars){
		return this.op.tpl(tplContent,vars);
	},
	ajaxError: function(xhr, ajaxOptions, thrownError)
	{
        window.TPMshowAjaxWait=true;
        TPM.info('网络异常');
	},

	
	//------实用工具
	//判断是否为IE
	isIE:function(){
		return /msie [\w.]+/.exec(navigator.userAgent.toLowerCase());
	},
	//判断是否为IE7以下浏览器
	isOldIE:function(){
		return this.isIE() && (!docMode || docMode <= 7);
	},
	//移动滚动条,n可以是数字也可以是对象
	scrollTop:function(n,t,obj){
		t=t||0;
        obj=obj ||'html,body'
		num=$.type(n)!="number"?n.offset().top:n;
		$(obj).animate( {
			scrollTop: num
		}, t );
	},
	//分离js代码	
	stripScripts:function(codes){
		var scripts = '';
		//将字符串去除script标签， 并获得script标签中的内容。
		var text = codes.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, function(all, code){
			scripts += code + '\n';
			return '';
		});
		return {text:text,scripts:scripts}
	},
	//执行js代码
	execScript:function(scripts,_box){
		if(scripts!=''){
			//执行js代码, 在闭包中执行。改变$选择符。 
			var e=new Function('$',scripts);
			var selector=function(obj){
                var $obj=$(obj,_box)
				return $obj.size()>0?$obj:$(obj);
			};
			e(selector);

		}
	},
	//判断变量是否定义
	defined:function(variable){
		return $.type(variable) == "undefined" ? false : true;	
	},
    //获得get参数
    get:function(name){
            if('undefined'==$.type(this._gets)){
                var querystring=window.location.search.substring(1);
                var gets={};
                var vars=querystring.split('&')
                var param;
                for(var i=0;i<vars.length;i++){
                    param=vars[i].split('=');
                    gets[param[0]]=param[1];
                } 
                this._gets=gets;
            }
            return this._gets[name];
    }

};





