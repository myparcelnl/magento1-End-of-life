<?php $helper = Mage::helper('tig_myparcel'); ?>
<style>
    /* Set base color */
    <?php $baseColor = $helper->getConfig('base_color', 'checkout');?>
    <?php if($baseColor != ''):?>

        .mypa-tab{
            background-color: #<?php echo $baseColor;?>;
            opacity: .5;
        }

        .mypa-delivery-header,
        .mypa-date:checked+label, .mypa-tab:hover
        {
            background: #<?php echo $baseColor;?>;
            opacity: 1;
        }

        .mypa-address {
            color: #<?php echo $baseColor;?>;
        }

        .edit-tip > div {
            border-top-color: #<?php echo $baseColor;?>;;
        }

        .edit-stem {
            background-color: #<?php echo $baseColor;?>;
        }

        #mypa-no-options {
            background: #<?php echo $baseColor;?>;
        }
    <?php endif;?>

    /* Set select color */
    <?php $selectColor = $helper->getConfig('select_color', 'checkout');?>
    <?php if($selectColor != ''):?>

        input:checked ~ .mypa-highlight, input:checked ~ label.mypa-row-title span.mypa-highlight,
        .mypa-arrow-clickable:hover
        {
            color: #<?php echo $selectColor; ?>;
        }

        input:checked + label.mypa-checkmark div.mypa-circle, input[name=mypa-delivery-type]:checked + label div.mypa-main div.mypa-circle, input[name=mypa-pickup-option]:checked + label div.mypa-main div.mypa-circle,
        .mypa-circle:hover, label.mypa-row-subitem:hover .mypa-circle,
        input:checked ~ .mypa-price, input:checked ~ span span.mypa-price
        {
            background-color: #<?php echo $selectColor; ?>;
        }

        .mypa-arrow-clickable:hover::before{
            border-left: 0.2em solid #<?php echo $selectColor;?>;
            border-bottom: 0.2em solid #<?php echo $selectColor;?>;
        }

        input:checked ~ .mypa-price, input:checked ~ span span.mypa-price, .mypa-price-active {
            background: #<?php echo $selectColor;?>;
        }
    <?php endif;?>
</style>