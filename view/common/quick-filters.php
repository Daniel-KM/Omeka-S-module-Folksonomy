<ul class="quick-filter-wrapper">
    <li><a href="#" tabindex="0"><?php echo __('Quick Filter'); ?></a>
    <ul class="dropdown">
        <li><span class="quick-filter-heading"><?php echo __('Quick Filter') ?></span></li>
        <li><a href="<?php echo url('tagging/index/browse'); ?>"><?php echo __('View All') ?></a></li>
        <li><a href="<?php echo url('tagging/index/browse', ['status' => 'not moderated']); ?>"><?php echo __('Not moderated'); ?></a></li>
        <li><a href="<?php echo url('tagging/index/browse', ['status' => 'moderated']); ?>"><?php echo __('Moderated'); ?></a></li>
        <li><a href="<?php echo url('tagging/index/browse', ['status' => 'proposed']); ?>"><?php echo __('Proposed'); ?></a></li>
        <li><a href="<?php echo url('tagging/index/browse', ['status' => 'allowed']); ?>"><?php echo __('Allowed'); ?></a></li>
        <li><a href="<?php echo url('tagging/index/browse', ['status' => 'approved']); ?>"><?php echo __('Approved'); ?></a></li>
        <li><a href="<?php echo url('tagging/index/browse', ['status' => 'rejected']); ?>"><?php echo __('Rejected'); ?></a></li>
    </ul>
    </li>
</ul>
