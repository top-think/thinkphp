(function ($) {

    var ie = $.browser.msie,
		iOS = /iphone|ipad|ipod/i.test(navigator.userAgent);

    $.TE = {
		version:'1.0', // 版本号
        debug: 1, //调试开关
        timeOut: 3000, //加载单个文件超时时间，单位为毫秒。
        defaults: {
            //默认参数controls,noRigths,plugins,定义加载插件
            controls: "source,|,undo,redo,|,cut,copy,paste,pastetext,selectAll,blockquote,|,image,flash,table,hr,pagebreak,face,code,|,link,unlink,|,print,fullscreen,|,eq,|,style,font,fontsize,|,fontcolor,backcolor,|,bold,italic,underline,strikethrough,unformat,|,leftalign,centeralign,rightalign,blockjustify,|,orderedlist,unorderedlist,indent,outdent,|,subscript,superscript",
            //noRights:"underline,strikethrough,superscript",
            width: 740,
            height: 500,
            skins: "default",
            resizeType: 2,
            face_path: ['qq_face', 'qq_face'],
            minHeight: 200,
            minWidth: 500,
            uploadURL: 'about:blank',
            theme: 'default'
        },
        buttons: {
            //按钮属性
            //eq: {title: '等于',cmd: 'bold'},
            bold: { title: "加粗", cmd: "bold" },
            pastetext: { title: "粘贴无格式", cmd: "bold" },
            pastefromword: { title: "粘贴word格式", cmd: "bold" },
            selectAll: { title: "全选", cmd: "selectall" },
            blockquote: { title: "引用" },
            find: { title: "查找", cmd: "bold" },
            flash: { title: "插入flash", cmd: "bold" },
            media: { title: "插入多媒体", cmd: "bold" },
            table: { title: "插入表格" },
            pagebreak: { title: "插入分页符" },
            face: { title: "插入表情", cmd: "bold" },
            code: { title: "插入源码", cmd: "bold" },
            print: { title: "打印", cmd: "print" },
            about: { title: "关于", cmd: "bold" },
            fullscreen: { title: "全屏", cmd: "fullscreen" },
            source: { title: "HTML代码", cmd: "source" },
            undo: { title: "后退", cmd: "undo" },
            redo: { title: "前进", cmd: "redo" },
            cut: { title: "剪贴", cmd: "cut" },
            copy: { title: "复制", cmd: "copy" },
            paste: { title: "粘贴", cmd: "paste" },
            hr: { title: "插入横线", cmd: "inserthorizontalrule" },
            link: { title: "创建链接", cmd: "createlink" },
            unlink: { title: "删除链接", cmd: "unlink" },
            italic: { title: "斜体", cmd: "italic" },
            underline: { title: "下划线", cmd: "underline" },
            strikethrough: { title: "删除线", cmd: "strikethrough" },
            unformat: { title: "清除格式", cmd: "removeformat" },
            subscript: { title: "下标", cmd: "subscript" },
            superscript: { title: "上标", cmd: "superscript" },
            orderedlist: { title: "有序列表", cmd: "insertorderedlist" },
            unorderedlist: { title: "无序列表", cmd: "insertunorderedlist" },
            indent: { title: "增加缩进", cmd: "indent" },
            outdent: { title: "减少缩进", cmd: "outdent" },
            leftalign: { title: "左对齐", cmd: "justifyleft" },
            centeralign: { title: "居中对齐", cmd: "justifycenter" },
            rightalign: { title: "右对齐", cmd: "justifyright" },
            blockjustify: { title: "两端对齐", cmd: "justifyfull" },
            font: { title: "字体", cmd: "fontname", value: "微软雅黑" },
            fontsize: { title: "字号", cmd: "fontsize", value: "4" },
            style: { title: "段落标题", cmd: "formatblock", value: "" },
            fontcolor: { title: "前景颜色", cmd: "forecolor", value: "#ff6600" },
            backcolor: { title: "背景颜色", cmd: "hilitecolor", value: "#ff6600" },
            image: { title: "插入图片", cmd: "insertimage", value: "" }
        },
        defaultEvent: {
            event: "click mouseover mouseout",
            click: function (e) {
                this.exec(e);
            },
            mouseover: function (e) {
                var opt = this.editor.opt;
                this.$btn.addClass(opt.cssname.mouseover);
            },
            mouseout: function (e) { },
            noRight: function (e) { },
            init: function (e) { },
            exec: function () {
                this.editor.restoreRange();
                //执行命令
                if ($.isFunction(this[this.cmd])) {
                    this[this.cmd](); //如果有已当前cmd为名的方法，则执行
                } else {
                    this.editor.doc.execCommand(this.cmd, 0, this.value || null);
                }
                this.editor.focus();
                this.editor.refreshBtn();
                this.editor.hideDialog();
            },
            createDialog: function (v) {
                //创建对话框
                var editor = this.editor,
				opt = editor.opt,
				$btn = this.$btn,
				_self = this;
                var defaults = {
                    body: "", //对话框内容
                    closeBtn: opt.cssname.dialogCloseBtn,
                    okBtn: opt.cssname.dialogOkBtn,
                    ok: function () {
                        //点击ok按钮后执行函数
                    },
                    setDialog: function ($dialog) {
                        //设置对话框（位置）
                        var y = $btn.offset().top + $btn.outerHeight();
                        var x = $btn.offset().left;
                        $dialog.offset({
                            top: y,
                            left: x
                        });
                    }
                };
                var options = $.extend(defaults, v);
                //初始化对话框
                editor.$dialog.empty();
                //加入内容
                $body = $.type(options.body) == "string" ? $(options.body) : options.body;
                $dialog = $body.appendTo(editor.$dialog);
                $dialog.find("." + options.closeBtn).click(function () { _self.hideDialog(); });
                $dialog.find("." + options.okBtn).click(options.ok);
                //设置对话框
                editor.$dialog.show();
                options.setDialog(editor.$dialog);
            },
            hideDialog: function () {
                this.editor.hideDialog();
            }
            //getEnable:function(){return false},
            //disable:function(e){alert('disable')}
        },
        plugin: function (name, v) {
            //新增或修改插件。
            $.TE.buttons[name] = $.extend($.TE.buttons[name], v);
        },
        config: function (name, value) {
            var _fn = arguments.callee;
            if (!_fn.conf) _fn.conf = {};

            if (value) {
                _fn.conf[name] = value;
                return true;
            } else {
                return name == 'default' ? $.TE.defaults : _fn.conf[name];
            }
        },
        systemPlugins: ['system', 'upload_interface'], //系统自带插件
        basePath: function () {
            var jsFile = "ThinkEditor.js";
            var src = $("script[src$='" + jsFile + "']").attr("src");
            return src.substr(0, src.length - jsFile.length);
        }
    };

    $.fn.extend({
        //调用插件
        ThinkEditor: function (v) {
            //配置处理
            var conf = '',
				temp = '';

            conf = v ? $.extend($.TE.config(v.theme ? v.theme : 'default'), v) : $.TE.config('default');

            v = conf;
            //配置处理完成

            //载入皮肤
            var skins = v.skins || $.TE.defaults.skins; //获得皮肤参数
            var skinsDir = $.TE.basePath() + "skins/" + skins + "/",
			jsFile = "@" + skinsDir + "config.js",
			cssFile = "@" + skinsDir + "style.css";

            var _self = this;
            //加载插件
            if ($.defined(v.plugins)) {
                var myPlugins = $.type(v.plugins) == "string" ? [v.plugins] : v.plugins;
                var files = $.merge($.merge([], $.TE.systemPlugins), myPlugins);
            } else {
                var files = $.TE.systemPlugins;
            }
            $.each(files, function (i, v) {
                files[i] = v + ".js";
            })

            files.push(jsFile, cssFile);
            files.push("@" + skinsDir + "dialog/css/base.css");
            files.push("@" + skinsDir + "dialog/css/te_dialog.css");

            $.loadFile(files, function () {
                //设置css参数
                v.cssname = $.extend({}, TECSS, v.cssname);
                //创建编辑器,存储对象
                $(_self).each(function (idx, elem) {
                    var data = $(elem).data("editorData");
                    if (!data) {
                        data = new ThinkEditor(elem, v);
                        $(elem).data("editorData", data);
                    }
                });

            });
        }

    });
    //编辑器对象。
    function ThinkEditor(area, v) {

        //添加随机序列数防冲突
        var _fn = arguments.callee;
        this.guid = !_fn.guid ? _fn.guid = 1 : _fn.guid += 1;

        //生成参数
        var opt = this.opt = $.extend({}, $.TE.defaults, v);
        var _self = this;
        //结构：主层，工具层，分组层，按钮层,底部,dialog层
        var $main = this.$main = $("<div></div>").addClass(opt.cssname.main),
			$toolbar_box = $('<div></div>').addClass(opt.cssname.toolbar_box).appendTo($main),
			$toolbar = this.$toolbar = $("<div></div>").addClass(opt.cssname.toolbar).appendTo($toolbar_box),
        /*$toolbar=this.$toolbar=$("<div></div>").addClass(opt.cssname.toolbar).appendTo($main),*/
			$group = $("<div></div>").addClass(opt.cssname.group).appendTo($toolbar),
			$bottom = this.$bottom = $("<div></div>").addClass(opt.cssname.bottom),
			$dialog = this.$dialog = $("<div></div>").addClass(opt.cssname.dialog),
			$area = $(area).hide(),
			$frame = $('<iframe frameborder="0"></iframe>');

        opt.noRights = opt.noRights || "";
        var noRights = opt.noRights.split(",");
        //调整结构
        $main.insertBefore($area)
		.append($area);
        //加入frame
        $frame.appendTo($main);
        //加入bottom
        if (opt.resizeType != 0) {
            //拖动改变编辑器高度
            $("<div></div>").addClass(opt.cssname.resizeCenter).mousedown(function (e) {
                var y = e.pageY,
				x = e.pageX,
				height = _self.$main.height(),
				width = _self.$main.width();
                $(document).add(_self.doc).mousemove(function (e) {
                    var mh = e.pageY - y;
                    _self.resize(width, height + mh);
                });
                $(document).add(_self.doc).mouseup(function (e) {
                    $(document).add(_self.doc).unbind("mousemove");
                    $(document).add(_self.doc).unbind("mousemup");
                });
            }).appendTo($bottom);
        }
        if (opt.resizeType == 2) {
            //拖动改变编辑器高度和宽度
            $("<div></div>").addClass(opt.cssname.resizeLeft).mousedown(function (e) {
                var y = e.pageY,
				x = e.pageX,
				height = _self.$main.height(),
				width = _self.$main.width();
                $(document).add(_self.doc).mousemove(function (e) {
                    var mh = e.pageY - y,
					mw = e.pageX - x;
                    _self.resize(width + mw, height + mh);
                });
                $(document).add(_self.doc).mouseup(function (e) {
                    $(document).add(_self.doc).unbind("mousemove");
                    $(document).add(_self.doc).unbind("mousemup");
                });
            }).appendTo($bottom);
        }
        $bottom.appendTo($main);
        $dialog.appendTo($main);
        //循环按钮处理。
        //TODO 默认参数处理
        $.each(opt.controls.split(","), function (idx, bname) {
            var _fn = arguments.callee;
            if (_fn.count == undefined) {
                _fn.count = 0;
            }

            //处理分组
            if (bname == "|") {
                //设定分组宽
                if (_fn.count) {
                    $toolbar.find('.' + opt.cssname.group + ':last').css('width', (opt.cssname.btnWidth * _fn.count + opt.cssname.lineWidth) + 'px');
                    _fn.count = 0;
                }
                //分组宽结束
                $group = $("<div></div>").addClass(opt.cssname.group).appendTo($toolbar);
                $("<div>&nbsp;</div>").addClass(opt.cssname.line).appendTo($group);

            } else {
                //更新统计数
                _fn.count += 1;
                //获取按钮属性
                var btn = $.extend({}, $.TE.defaultEvent, $.TE.buttons[bname]);
                //标记无权限
                var noRightCss = "", noRightTitle = "";
                if ($.inArray(bname, noRights) != -1) {
                    noRightCss = " " + opt.cssname.noRight;
                    noRightTitle = "(无权限)";
                }
                $btn = $("<div></div>").addClass(opt.cssname.btn + " " + opt.cssname.btnpre + bname + noRightCss)
				.data("bname", bname)
				.attr("title", btn.title + noRightTitle)
				.appendTo($group)
				.bind(btn.event, function (e) {
				    //不可用触发
				    if ($(this).is("." + opt.cssname.disable)) {
				        if ($.isFunction(btn.disable)) btn.disable.call(btn, e);
				        return false;
				    }
				    //判断权限和是否可用
				    if ($(this).is("." + opt.cssname.noRight)) {
				        //点击时触发无权限说明
				        btn['noRight'].call(btn, e);
				        return false;
				    }
				    if ($.isFunction(btn[e.type])) {
				        //触发事件
				        btn[e.type].call(btn, e);
				        //TODO 刷新按钮
				    }
				});
                if ($.isFunction(btn.init)) btn.init.call(btn); //初始化
                if (ie) $btn.attr("unselectable", "on");
                btn.editor = _self;
                btn.$btn = $btn;
            }
        });
        //调用核心
        this.core = new editorCore($frame, $area);
        this.doc = this.core.doc;
        this.$frame = this.core.$frame;
        this.$area = this.core.$area;
        this.restoreRange = this.core.restoreRange;
        this.selectedHTML = function () { return this.core.selectedHTML(); }
        this.selectedText = function () { return this.core.selectedText(); }
        this.pasteHTML = function (v) { this.core.pasteHTML(v); }
        this.sourceMode = this.core.sourceMode;
        this.focus = this.core.focus;
        //监控变化
        $(this.core.doc).click(function () {
            //隐藏对话框
            _self.hideDialog();
        }).bind("keyup mouseup", function () {
            _self.refreshBtn();
        })
        this.refreshBtn();
        //调整大小
        this.resize(opt.width, opt.height);

        //获取DOM层级
        this.core.focus();
    }
    //end ThinkEditor
    ThinkEditor.prototype.resize = function (w, h) {
        //最小高度和宽度
        var opt = this.opt,
		h = h < opt.minHeight ? opt.minHeight : h,
		w = w < opt.minWidth ? opt.minWidth : w;
        this.$main.width(w).height(h);
        var height = h - (this.$toolbar.parent().outerHeight() + this.$bottom.height());
        this.$frame.height(height).width("100%");
        this.$area.height(height).width("100%");
    };
    //隐藏对话框
    ThinkEditor.prototype.hideDialog = function () {
        var opt = this.opt;
        $("." + opt.cssname.dialog).hide();
    };
    //刷新按钮
    ThinkEditor.prototype.refreshBtn = function () {
        var sourceMode = this.sourceMode(); // 标记状态。
        var opt = this.opt;
        if (!iOS && $.browser.webkit && !this.focused) {
            this.$frame[0].contentWindow.focus();
            window.focus();
            this.focused = true;
        }
        var queryObj = this.doc;
        if (ie) queryObj = this.core.getRange();
        //循环按钮
        //TODO undo,redo等判断
        this.$toolbar.find("." + opt.cssname.btn + ":not(." + opt.cssname.noRight + ")").each(function () {
            var enabled = true,
			btnName = $(this).data("bname"),
			btn = $.extend({}, $.TE.defaultEvent, $.TE.buttons[btnName]),
			command = btn.cmd;
            if (sourceMode && btnName != "source") {
                enabled = false;
            } else if ($.isFunction(btn.getEnable)) {
                enabled = btn.getEnable.call(btn);
            } else if ($.isFunction(btn[command])) {
                enabled = true; //如果命令为自定义命令，默认为可用
            } else {
                if (!ie || btn.cmd != "inserthtml") {
                    try {
                        enabled = queryObj.queryCommandEnabled(command);
                        $.debug(enabled.toString(), "命令:" + command);
                    }
                    catch (err) {
                        enabled = false;
                    }
                }

                //判断该功能是否有实现 @TODO 代码胶着
                if ($.TE.buttons[btnName]) enabled = true;
            }
            if (enabled) {
                $(this).removeClass(opt.cssname.disable);
            } else {
                $(this).addClass(opt.cssname.disable);
            }
        });
    };
    //core code start
    function editorCore($frame, $area, v) {
        //TODO 参数改为全局的。
        var defaults = {
            docType: '<!DOCTYPE HTML>',
            docCss: "",
            bodyStyle: "margin:4px; font:10pt Arial,Verdana; cursor:text",
            focusExt: function (editor) {
                //触发编辑器获得焦点时执行，比如刷新按钮
            },
            //textarea内容更新到iframe的处理函数
            updateFrame: function (code) {
                //翻转flash为占位符
                code = code.replace(/(<embed[^>]*?type="application\/x-shockwave-flash" [^>]*?>)/ig, function ($1) {
                    var ret = '<img class="_flash_position" src="' + $.TE.basePath() + 'skins/default/img/spacer.gif" style="',
						_width = $1.match(/width="(\d+)"/),
						_height = $1.match(/height="(\d+)"/),
						_src = $1.match(/src="([^"]+)"/),
						_wmode = $1.match(/wmode="(\w+)"/),
						_data = '';

                    _width = _width && _width[1] ? parseInt(_width[1]) : 0;
                    _height = _height && _height[1] ? parseInt(_height[1]) : 0;
                    _src = _src && _src[1] ? _src[1] : '';
                    _wmode = _wmode && _wmode[1] ? true : false;
                    _data = "{'src':'" + _src + "','width':'" + _width + "','height':'" + _height + "','wmode':" + (_wmode) + "}";


                    if (_width) ret += 'width:' + _width + 'px;';
                    if (_height) ret += 'height:' + _height + 'px;';

                    ret += 'border:1px solid #DDD; display:inline-block;text-align:center;line-height:' + _height + 'px;" ';
                    ret += '_data="' + _data + '"';
                    ret += ' alt="flash占位符" />';

                    return ret;
                });

                return code;
            },
            //iframe更新到text的， TODO 去掉
            updateTextArea: function (html) {
                //翻转占位符为flash
                html = html.replace(/(<img[^>]*?class=(?:"|)_flash_position(?:"|)[^>]*?>)/ig, function ($1) {
                    var ret = '',
						data = $1.match(/_data="([^"]*)"/);

                    if (data && data[1]) {
                        data = eval('(' + data + ')');
                    }

                    ret += '<embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" ';
                    ret += 'src="' + data.src + '" ';
                    ret += 'width="' + data.width + '" ';
                    ret += 'height="' + data.height + '" ';
                    if (data.wmode) ret += 'wmode="transparent" ';
                    ret += '/>';

                    return ret;
                });

                return html;
            }
        };
        options = $.extend({}, defaults, v);
        //存储属性
        this.opt = options;
        this.$frame = $frame;
        this.$area = $area;
        var contentWindow = $frame[0].contentWindow,
		doc = this.doc = contentWindow.document,
		$doc = $(doc);

        var _self = this;

        //初始化
        doc.open();
        doc.write(
			options.docType +
			'<html>' +
			((options.docCss === '') ? '' : '<head><link rel="stylesheet" type="text/css" href="' + options.docCss + '" /></head>') +
			'<body style="' + options.bodyStyle + '"></body></html>'
			);
        doc.close();
        //设置frame编辑模式
        try {
            if (ie) {
                doc.body.contentEditable = true;
            }
            else {
                doc.designMode = "on";
            }
        } catch (err) {
            $.debug(err, "创建编辑模式错误");
        }

        //统一 IE FF 等的 execCommand 行为
        try {
            this.e.execCommand("styleWithCSS", 0, 0)
        }
        catch (e) {
            try {
                this.e.execCommand("useCSS", 0, 1);
            } catch (e) { }
        }

        //监听
        if (ie)
            $doc.click(function () {
                _self.focus();
            });
        this.updateFrame(); //更新内容

        if (ie) {
            $doc.bind("beforedeactivate beforeactivate selectionchange keypress", function (e) {
                if (e.type == "beforedeactivate")
                    _self.inactive = true;

                else if (e.type == "beforeactivate") {
                    if (!_self.inactive && _self.range && _self.range.length > 1)
                        _self.range.shift();
                    delete _self.inactive;
                }

                else if (!_self.inactive) {
                    if (!_self.range)
                        _self.range = [];
                    _self.range.unshift(_self.getRange());

                    while (_self.range.length > 2)
                        _self.range.pop();
                }

            });

            // Restore the text range when the iframe gains focus
            $frame.focus(function () {
                _self.restoreRange();
            });
        }

        ($.browser.mozilla ? $doc : $(contentWindow)).blur(function () {
            _self.updateTextArea(true);
        });
        this.$area.blur(function () {
            // Update the iframe when the textarea loses focus
            _self.updateFrame(true);
        });

        /*
        * //自动添加p标签
        * $doc.keydown(function(e){
        * 	if(e.keyCode == 13){
        * 		//_self.pasteHTML('<p>&nbsp;</p>');
        * 		//this.execCommand( 'formatblock', false, '<p>' );
        * 	}
        * });
        */

    }
    //是否为源码模式
    editorCore.prototype.sourceMode = function () {
        return this.$area.is(":visible");
    };
    //编辑器获得焦点
    editorCore.prototype.focus = function () {
        var opt = this.opt;
        if (this.sourceMode()) {
            this.$area.focus();
        }
        else {
            this.$frame[0].contentWindow.focus();
        }
        if ($.isFunction(opt.focusExt)) opt.focusExt(this);
    };
    //textarea内容更新到iframe
    editorCore.prototype.updateFrame = function (checkForChange) {
        var code = this.$area.val(),
		options = this.opt,
		updateFrameCallback = options.updateFrame,
		$body = $(this.doc.body);
        //判断是否已经修改
        if (updateFrameCallback) {
            var sum = checksum(code);
            if (checkForChange && this.areaChecksum == sum)
                return;
            this.areaChecksum = sum;
        }

        //回调函数处理
        var html = updateFrameCallback ? updateFrameCallback(code) : code;

        // 禁止script标签

        html = html.replace(/<(?=\/?script)/ig, "&lt;");

        // TODO，判断是否有作用
        if (options.updateTextArea)
            this.frameChecksum = checksum(html);

        if (html != $body.html()) {
            $body.html(html);
        }
    };
    editorCore.prototype.getRange = function () {
        if (ie) return this.getSelection().createRange();
        return this.getSelection().getRangeAt(0);
    };

    editorCore.prototype.getSelection = function () {
        if (ie) return this.doc.selection;
        return this.$frame[0].contentWindow.getSelection();
    };
    editorCore.prototype.restoreRange = function () {
        if (ie && this.range)
            this.range[0].select();
    };

    editorCore.prototype.selectedHTML = function () {
        this.restoreRange();
        var range = this.getRange();
        if (ie)
            return range.htmlText;
        var layer = $("<layer>")[0];
        layer.appendChild(range.cloneContents());
        var html = layer.innerHTML;
        layer = null;
        return html;
    };


    editorCore.prototype.selectedText = function () {
        this.restoreRange();
        if (ie) return this.getRange().text;
        return this.getSelection().toString();
    };

    editorCore.prototype.pasteHTML = function (value) {
        this.restoreRange();
        if (ie) {
            this.getRange().pasteHTML(value);
        } else {
            this.doc.execCommand("inserthtml", 0, value || null);
        }
        //获得焦点
        this.$frame[0].contentWindow.focus();
    }

    editorCore.prototype.updateTextArea = function (checkForChange) {
        var html = $(this.doc.body).html(),
		options = this.opt,
		updateTextAreaCallback = options.updateTextArea,
		$area = this.$area;


        if (updateTextAreaCallback) {
            var sum = checksum(html);
            if (checkForChange && this.frameChecksum == sum)
                return;
            this.frameChecksum = sum;
        }


        var code = updateTextAreaCallback ? updateTextAreaCallback(html) : html;
        // TODO 判断是否有必要
        if (options.updateFrame)
            this.areaChecksum = checksum(code);
        if (code != $area.val()) {
            $area.val(code);
        }

    };
    function checksum(text) {
        var a = 1, b = 0;
        for (var index = 0; index < text.length; ++index) {
            a = (a + text.charCodeAt(index)) % 65521;
            b = (b + a) % 65521;
        }
        return (b << 16) | a;
    }
    $.extend({
        teExt: {
        //扩展配置
    },
    debug: function (msg, group) {
        //判断是否有console对象
        if ($.TE.debug && window.console !== undefined) {
            //分组开始
            if (group) console.group(group);
            if ($.type(msg) == "string") {
                //是否为执行特殊函数,用双冒号隔开
                if (msg.indexOf("::") != -1) {
                    var arr = msg.split("::");
                    eval("console." + arr[0] + "('" + arr[1] + "')");
                } else {
                    console.debug(msg);
                }
            } else {
                if ($(msg).html() == null) {
                    console.dir(msg); //输出对象或数组
                } else {
                    console.dirxml($(msg)[0]); //输出dom对象
                }

            }
            //记录trace信息
            if ($.TE.debug == 2) {
                console.group("trace 信息:");
                console.trace();
                console.groupEnd();
            }
            //分组结束
            if (group) console.groupEnd();
        }
    },
    //end debug
    defined: function (variable) {
        return $.type(variable) == "undefined" ? false : true;
    },
    isTag: function (tn) {
        if (!tn) return false;
        return $(this)[0].tagName.toLowerCase() == tn ? true : false;
    },
    //end istag
    include: function (file) {
        if (!$.defined($.TE.loadUrl)) $.TE.loadUrl = {};
        //定义皮肤路径和插件路径。
        var basePath = $.TE.basePath(),
			skinsDir = basePath + "skins/",
			pluginDir = basePath + "plugins/";
        var files = $.type(file) == "string" ? [file] : file;
        for (var i = 0; i < files.length; i++) {
            var loadurl = name = $.trim(files[i]);
            //判断是否已经加载过
            if ($.TE.loadUrl[loadurl]) {
                continue;
            }
            //判断是否有@
            var at = false;
            if (name.indexOf("@") != -1) {
                at = true;
                name = name.substr(1);
            }
            var att = name.split('.');
            var ext = att[att.length - 1].toLowerCase();
            if (ext == "css") {
                //加载css
                var filepath = at ? name : skinsDir + name;
                var newNode = document.createElement("link");
                newNode.setAttribute('type', 'text/css');
                newNode.setAttribute('rel', 'stylesheet');
                newNode.setAttribute('href', filepath);
                $.TE.loadUrl[loadurl] = 1;
            } else {
                var filepath = at ? name : pluginDir + name;
                //$("<scri"+"pt>"+"</scr"+"ipt>").attr({src:filepath,type:'text/javascript'}).appendTo('head');
                var newNode = document.createElement("script");
                newNode.type = "text/javascript";
                newNode.src = filepath;
                newNode.id = loadurl; //实现批量加载
                newNode.onload = function () {
                    $.TE.loadUrl[this.id] = 1;
                };
                newNode.onreadystatechange = function () {
                    //针对ie
                    if ((newNode.readyState == 'loaded' || newNode.readyState == 'complete')) {
                        $.TE.loadUrl[this.id] = 1;
                    }
                };
            }
            $("head")[0].appendChild(newNode);
        }
    },
    //end include
    loadedFile: function (file) {
        //判断是否加载
        if (!$.defined($.TE.loadUrl)) return false;
        var files = $.type(file) == "string" ? [file] : file,
			result = true;
        $.each(files, function (i, name) {
            if (!$.TE.loadUrl[name]) result = false;
			//alert(name+':'+result);
        });
		
        return result;		
    },
    //end loaded

    loadFile: function (file, fun) {
        //加载文件，加载完毕后执行fun函数。
        $.include(file);
		
        var time = 0;
        var check = function () {
			//alert($.loadedFile(file));
            if ($.loadedFile(file)) {
                if ($.isFunction(fun)) fun();
            } else {
				//alert(time);
                if (time >= $.TE.timeOut) {
                    // TODO 细化哪些文件加载失败。
                    $.debug(file, "文件加载失败");
                } else {
					//alert('time:'+time);
                    setTimeout(check, 50);
                    time += 50;
                }
            }
        };
        check();
    }
    //end loadFile
});

})(jQuery);

jQuery.TE.config( 'mini', {
	'controls' : 'font,fontsize,fontcolor,backcolor,bold,italic,underline,unformat,leftalign,centeralign,rightalign,orderedlist,unorderedlist',
	'width':498,
	'height':400,
	'resizeType':1
} );