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
        
        <!-- 登录日志对话框 -->
        <LoginLog ref="loginLogRef" />
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
import LoginLog from './loginLog.vue'
import { timeFormat } from '/@/utils/common'

defineOptions({
    name: 'user/user',
})

const { t } = useI18n()
const tableRef = ref()
const loginLogRef = ref()
let optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

let newButton: OptButton[] = [
    {
        // 渲染方式:tipButton=带tip的按钮,confirmButton=带确认框的按钮,moveButton=移动按钮
        render: 'tipButton',
        // 按钮名称
        name: 'info',
        // 鼠标放置时的 title 提示
        title: '登录日志',
        // 直接在按钮内显示的文字，title 有值时可为空
        text: '登录日志',
        // 按钮类型，请参考 element plus 的按钮类型
        type: 'primary',
        // 按钮 icon
        icon: 'fa fa-search-plus',
        class: 'table-row-info',
        // tipButton 禁用 tip
        disabledTip: false,
        // 自定义点击事件
        click: (row: TableRow) => {
            loginLogRef.value?.open(row.id)
        },
        // 按钮是否显示，请返回布尔值
        display: (row: TableRow, field: TableColumn) => {
            return true
        },
        // 按钮是否禁用，请返回布尔值
        disabled: (row: TableRow, field: TableColumn) => {
            return false
        },
        // 自定义el-button属性
        attr: {}
    },
]
optButtons = newButton.concat(optButtons)
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
            // { label: t('user.user.theme_id'), prop: 'theme_id', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE' },
            // { label: '上次登录时间', prop: 'last_login_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            { 
                label: '上次登录', 
                prop: 'last_login_time', 
                align: 'center', 
                operator: 'RANGE', 
                sortable: 'custom', 
                width: 160,
                render: 'tag',
                custom:{'三天内有登录':'primary','三天内未登录':'warning','七天内未登录':'danger'},
                formatter: (row, column, cellValue, index) => {
                    const lastLoginTime = new Date(row.last_login_time).getTime()*1000
                    const now = new Date().getTime()
                    
                    const threeDays = 3 * 24 * 60 * 60 * 1000
                    const sevenDays = 7 * 24 * 60 * 60 * 1000
                    const diffTime = now - lastLoginTime

                    let className = ''
                    if (diffTime > sevenDays) {
                       return '七天内未登录'
                    } else if (diffTime > threeDays) {
                        return '三天内未登录'
                    }
                    return '三天内有登录'
                },
                
            },
            { label: t('Operate'), align: 'center', width: 200, render: 'buttons', buttons: optButtons, operator: false },
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
