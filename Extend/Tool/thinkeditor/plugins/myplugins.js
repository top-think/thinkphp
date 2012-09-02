$.TE.plugin("bold",{
    title:"标题",
    cmd:"bold",
    click:function(){
        this.editor.pasteHTML("sdfdf");
    },
    bold:function(){
        alert('sdfsdf');
    },
    noRight:function(e){
        if(e.type=="click"){
            alert('noright');
        }
    },
    init:function(){
    },
    event:"click mouseover mouseout",
    mouseover:function(e){
        this.$btn.css("color","red");
	
    },
    mouseout:function(e){
        this.$btn.css("color","#000")
    }
});
		