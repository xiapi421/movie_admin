<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('pay.quick Search Fields') })"
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
import { clearPayApi } from '/@/api/backend/real'
defineOptions({
    name: 'pay',
})

const { t } = useI18n()
const tableRef = ref()
let optButtons: OptButton[] = defaultOptButtons(['weigh-sort', 'edit', 'delete'])
let newButton: OptButton[] = [
    {
        // 渲染方式:tipButton=带tip的按钮,confirmButton=带确认框的按钮,moveButton=移动按钮
        render: 'confirmButton',
        // 按钮名称
        name: 'clear',
        // 鼠标放置时的 title 提示
        title: '清空数据',
        // 直接在按钮内显示的文字，title 有值时可为空
        text: '清空数据',
        // 按钮类型，请参考 element plus 的按钮类型
        type: 'primary',
        popconfirm: {
            title: '确认清空该通道数据吗？',
            okText: '确认',
            cancelText: '取消',
        },
        // 按钮 icon
        icon: 'el-icon-Delete',
        class: 'table-row-info',
        // tipButton 禁用 tip
        disabledTip: false,
        // 自定义点击事件
        click: (row: TableRow) => {
            console.log(row)
            clearPayApi(row.id).then((res) => {
                console.log(res)
                if (res.code == 1) {
                    baTable.getIndex()?.then(() => {
                        baTable.initSort()
                        baTable.dragSort()
                    })
                }
            })
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
optButtons = optButtons.concat(newButton)

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/pay/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('pay.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            { label: t('pay.name'), prop: 'name', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('pay.select'), prop: 'select', align: 'center', render: 'tag', operator: 'eq', sortable: false, replaceValue: { alipay: t('pay.select alipay'), wechat: t('pay.select wechat') } },
            // { label: t('pay.weigh'), prop: 'weigh', align: 'center', operator: 'RANGE', sortable: 'custom' },
            { label: t('pay.total_money'), prop: 'total_money', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('pay.total_order'), prop: 'total_order', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('pay.today_money'), prop: 'today_money', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('pay.today_order'), prop: 'today_order', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('pay.lastday_money'), prop: 'lastday_money', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('pay.lastday_order'), prop: 'lastday_order', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('pay.remark'), prop: 'remark', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('pay.status'), prop: 'status', align: 'center', render: 'switch', operator: 'eq', sortable: false, replaceValue: { '0': t('pay.status 0'), '1': t('pay.status 1') } },
            { label: t('pay.create_time'), prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            // { label: t('pay.update_time'), prop: 'update_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            { label: t('Operate'), align: 'center', width: 250, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined, 'status'],
        defaultOrder: { prop: 'weigh', order: 'desc' },
    },
    {
        defaultItems: { select: 'alipay', status: '1' },
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
