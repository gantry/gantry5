## All avaialbe structure types that can occur ##
        {% if field.accept is defined %}accept="{{ field.accept }}"{% endif %}
        {% if field.alt is defined %}alt="{{ field.alt }}"{% endif %}
        {% if field.autocomplete in ['on', 'off'] %}autocomplete="{{ field.autocomplete }}"{% endif %}
        {% if field.autofocus in ['on', 'true', 1] %}autofocus="autofocus"{% endif %}
        {% if field.dirname is defined %}dirname="{{ field.dirname }}"{% endif %}
        {% if field.disabled in ['on', 'true', 1] %}disabled="disabled"{% endif %}
        {% if field.height is defined %}height="{{ field.height }}"{% endif %}
        {% if field.list is defined %}list="{{ field.list }}"{% endif %}
        {% if field.max is defined %}max="{{ field.max }}"{% endif %}
        {% if field.maxlength is defined %}maxlength="{{ field.maxlength }}"{% endif %}
        {% if field.min is defined %}min="{{ field.min }}"{% endif %}
        {% if field.minlength is defined %}minlength="{{ field.minlength }}"{% endif %}
        {% if field.multiple in ['on', 'true', 1] %}multiple="multiple"{% endif %}
        {% if field.pattern is defined %}pattern="{{ field.pattern }}"{% endif %}
        {% if field.placeholder is defined %}placeholder="{{ field.placeholder }}"{% endif %}
        {% if field.readonly in ['on', 'true', 1] %}readonly="readonly"{% endif %}
        {% if field.required in ['on', 'true', 1] %}required="required"{% endif %}
        {% if field.size is defined %}size="{{ field.size }}"{% endif %}
        {% if field.src is defined %}src="{{ field.src }}"{% endif %}
        {% if field.step is defined %}step="{{ field.step }}"{% endif %}
        {% if field.width is defined %}width="{{ field.width }}"{% endif %}
        
[http://www.w3.org/TR/html5/forms.html#the-input-element] (http://www.w3.org/TR/html5/forms.html#the-input-element)