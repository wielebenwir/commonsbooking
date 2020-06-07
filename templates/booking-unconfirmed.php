<?php
    $params = \CommonsBooking\View\Booking::unconfirmed();
?>

<div class="alignwide">
    <div>
        <h3><?php $params['end_date_string'] ?> - {{ end_date_string }}</h3>
    </div>

    <div>
        {% include '/post/preview.html.twig'  with {'post': location.post} %}
    </div>

    <div>
        {% include '/post/preview.html.twig'  with {'post': item.post} %}
    </div>

    <div>
        <h3><a href="{{ user|get_link }}">{{ user.display_name }}</a></h3>
        E-Mail. {{ user.user_email }}
    </div>

    <div>
        {% include '/timeframe/form.html.twig' %}
    </div>
</div>
