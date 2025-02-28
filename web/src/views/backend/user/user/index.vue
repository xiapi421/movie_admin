<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('user.user.quick Search Fields') })"
        ></TableHeader>

        <!-- 表格 -->
        <!-- 表格列有多种自定义渲染方式，比如自定义组件、具名插槽等，参见文档 -->
        <!-- 要使用 el-table 组件原有的属性，直接加在 Table 标签上即可 -->
        <Table ref="tableRef"></Table>

        <!-- 表单 -->
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { onMounted, provide, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import { baTableApi } from '/@/api/common'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'

defineOptions({
    name: 'user/user',
})

const { t } = useI18n()
const tableRef = ref()
const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/user.User/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('user.user.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            // { label: t('user.user.group_id'), prop: 'group_id', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE' },
            { label: t('user.user.username'), prop: 'username', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('user.user.money'), prop: 'money', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('user.user.invite_code'), prop: 'invite_code', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            // { label: t('user.user.score'), prop: 'score', align: 'center', operator: 'RANGE', sortable: false },
            // { label: t('user.user.last_login_ip'), prop: 'last_login_ip', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            // { label: t('user.user.password'), prop: 'password', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            // { label: t('user.user.salt'), prop: 'salt', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            // { label: t('user.user.update_time'), prop: 'update_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },

            { label: t('user.user.status'), prop: 'status', align: 'center', render: 'switch', operator: 'eq', sortable: false, replaceValue: { '0': t('user.user.status 0'), '1': t('user.user.status 1') } },
            { label: t('user.user.share_status'), prop: 'share_status', align: 'center', render: 'switch', operator: 'eq', sortable: false, replaceValue: { '0': t('user.user.share_status 0'), '1': t('user.user.share_status 1') } },
            { label: t('user.user.pay_status'), prop: 'pay_status', align: 'center', render: 'switch', operator: 'eq', sortable: false, replaceValue: { '0': t('user.user.pay_status 0'), '1': t('user.user.pay_status 1') } },
            { label: t('user.user.withdraw_status'), prop: 'withdraw_status', align: 'center', render: 'switch', operator: 'eq', sortable: false, replaceValue: { '0': t('user.user.withdraw_status 0'), '1': t('user.user.withdraw_status 1') } },
            { label: t('user.user.lastday_sell'), prop: 'lastday_sell', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('user.user.today_sell'), prop: 'today_sell', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('user.user.lastday_money'), prop: 'lastday_money', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('user.user.single_price'), prop: 'single_price', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('user.user.day_price'), prop: 'day_price', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('user.user.week_price'), prop: 'week_price', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('user.user.month_price'), prop: 'month_price', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('user.user.theme_id'), prop: 'theme_id', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE' },
            { label: '上次登录时间', prop: 'last_login_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            { label: t('Operate'), align: 'center', width: 100, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined],
    },
    {
        defaultItems: { status: '1', share_status: '1', pay_status: '1', withdraw_status: '1' },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getIndex()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
})
</script>

<style scoped lang="scss"></style>
