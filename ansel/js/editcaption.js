Ajax.InPlaceEditor.prototype.__initialize=Ajax.InPlaceEditor.prototype.initialize;Ajax.InPlaceEditor.prototype.__getText=Ajax.InPlaceEditor.prototype.getText;Object.extend(Ajax.InPlaceEditor.prototype,{initialize:function(C,B,A){this.__initialize(C,B,A);this.setOptions(A);this.checkEmpty()},setOptions:function(A){this.options=Object.extend(Object.extend(this.options,{emptyClassName:"inplaceeditor-empty"}),A||{})},checkEmpty:function(){if(this.element.innerHTML.length==0){emptyNode=new Element("span",{className:this.options.emptyClassName}).update(this.options.emptyText);this.element.appendChild(emptyNode)}},getText:function(){$(this.element).select("."+this.options.emptyClassName).each(function(A){this.element.removeChild(A)}.bind(this));return this.__getText()}});function tileExit(B,A){B.checkEmpty()};