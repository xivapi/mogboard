class ButtonLoading
{
    start($ele)
    {
        $ele.attr('data-text', $ele.html());

        $ele.addClass('btn_loading');
        $ele.prop('disabled', true);
        $ele.html('<i class="icon-load-c"></i>');
    }

    finish($ele)
    {
        const text = $ele.attr('data-text');

        $ele.removeClass('btn_loading');
        $ele.prop('disabled', false);
        $ele.html(text);
    }

    disable($ele, text)
    {
        $ele.attr('data-text',$ele.html());

        $ele.removeClass('btn_loading');
        $ele.prop('disabled', true);
        $ele.html(text);
    }
}

export default new ButtonLoading;
