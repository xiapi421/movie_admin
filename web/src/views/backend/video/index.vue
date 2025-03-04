<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('video.quick Search Fields') })"
        >
            <template #default>
                <el-button v-blur class="table-header-operate" type="danger" style="margin-left: 10px;" @click="updateJson()">
                    <Icon color="#ffffff" name="el-icon-RefreshRight" />
                    <span class="table-header-operate-text">一键更新静态资源</span>
                </el-button>

                <el-button v-blur class="table-header-operate" type="danger" @click="autoUpdateJson()">
                    <Icon color="#ffffff" name="el-icon-CaretRight" />
                    <span class="table-header-operate-text">定时更新静态资源</span>
                </el-button>

                <el-button v-blur class="table-header-operate" type="danger" @click="clearHot()">
                    <Icon color="#ffffff" name="el-icon-Delete" />
                    <span class="table-header-operate-text">清空热门数据</span>
                </el-button>

                <el-button v-blur class="table-header-operate" type="success" @click="importVideos()">
                    <Icon color="#ffffff" name="el-icon-Files" />
                    <span class="table-header-operate-text">导入视频</span>
                </el-button>

                <el-button v-blur class="table-header-operate" type="warning" @click="updateVideoDomain()">
                    <Icon color="#ffffff" name="el-icon-Files" />
                    <span class="table-header-operate-text">一键修改视频域名</span>
                </el-button>

                <el-button v-blur class="table-header-operate" type="warning" @click="updateImageDomain()">
                    <Icon color="#ffffff" name="el-icon-Files" />
                    <span class="table-header-operate-text">一键修改图片域名</span>
                </el-button>
            </template>
        </TableHeader>

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
import { updateJsonApi, importVideoApi, updateImageDomainApi, updateVideoDomainApi } from '/@/api/backend/real'
import Icon from "/@/components/icon/index.vue";
import { ElMessage, ElMessageBox } from 'element-plus'

defineOptions({
    name: 'video',
})

const { t } = useI18n()
const tableRef = ref()
const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])
// optButtons[2]=
const updateJson = () => {
    console.log('updateJson');
    updateJsonApi().then(() => {

    })
}

const autoUpdateJson = () => {
    console.log('autoUpdateJson');
}

const clearHot = () => {
    console.log('clearHot');
}

const importVideos = () => {
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = '.txt,.csv'
    input.onchange = (e: Event) => {
        const file = (e.target as HTMLInputElement).files?.[0]
        if (file) {
            const formData = new FormData()
            formData.append('file', file)
            importVideoApi(formData).then((res) => {
                ElMessage.success('导入成功')
                baTable.getIndex()
            }).catch((err) => {
                ElMessage.error('导入失败：' + err.message)
            })
        }
    }
    input.click()
}

const updateVideoDomain = () => {
    ElMessageBox.prompt('请输入新的视频域名', '修改视频域名', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        inputPlaceholder: '请输入域名，例如: http://example.com',
        inputPattern: /^https?:\/\/.+/,
        inputErrorMessage: '请输入正确的域名格式(以http://或https://开头)'
    }).then(({ value }) => {
        // 这里添加调用API的逻辑
        updateVideoDomainApi(value).then(() => {
            ElMessage.success('域名修改成功')
            baTable.getIndex()
        }).catch(() => {
            ElMessage.error('域名修改失败')
        })
    }).catch(() => {
        // 取消操作时不做任何处理
    })
}

const updateImageDomain = () => {
    ElMessageBox.prompt('请输入新的图片域名', '修改图片域名', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        inputPlaceholder: '请输入域名，例如: http://example.com',
        inputPattern: /^https?:\/\/.+/,
        inputErrorMessage: '请输入正确的域名格式(以http://或https://开头)'
    }).then(({ value }) => {
        // 这里添加调用API的逻辑
        updateImageDomainApi(value).then(() => {
            ElMessage.success('域名修改成功')
            baTable.getIndex()
        }).catch(() => {
            ElMessage.error('域名修改失败')
        })
    }).catch(() => {
        // 取消操作时不做任何处理
    })
}

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/Video/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('video.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            { label: t('video.videocategory__name'), prop: 'videoCategory.name', align: 'center', operatorPlaceholder: t('Fuzzy query'), render: 'tags', operator: 'LIKE', width:100 },
            { label: t('video.name'), prop: 'name', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false, width:200 },
            { label: t('video.image'), prop: 'image', align: 'center', render: 'image', operator: false ,width:160 },
            { label: '视频地址', prop: 'url', align: 'center', operatorPlaceholder: t('Fuzzy query'),  operator: 'LIKE', sortable: false, width:200 },
            { label: '时长', prop: 'duration', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: '总浏览', prop: 'total_views', align: 'center', render: 'tag', operator: 'RANGE', sortable: true, replaceValue: { } ,width:100},
            { label: '总点击', prop: 'total_clicks', align: 'center', render: 'tag',operator: 'RANGE', sortable: true ,width:100},
            { label: t('video.total_purchases'), prop: 'total_purchases', align: 'center',render: 'tag', operator: 'RANGE', sortable: true ,width:100},
            { label: '总转化%', prop: 'total_conversion_rate',render: 'tag', align: 'center', operator: 'RANGE', sortable: true ,width:110},
            { label: t('video.today_views'), prop: 'today_views', align: 'center', render: 'tag',operator: 'RANGE', sortable: true ,width:110},
            { label: t('video.today_clicks'), prop: 'today_clicks', align: 'center', render: 'tag',operator: 'RANGE', sortable: true ,width:110},
            { label: t('video.today_purchases'), prop: 'today_purchases', align: 'center',render: 'tag', operator: 'RANGE', sortable: true ,width: 110},
            { label: t('video.today_conversion_rate'), prop: 'today_conversion_rate', align: 'center',render: 'tag', operator: 'RANGE', sortable: true,width: 120},
            { label: t('video.create_time'), prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            // { label: t('video.update_time'), prop: 'update_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            { label: t('Operate'), align: 'center', width: 100, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined],
    },
    {
        defaultItems: {},
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
