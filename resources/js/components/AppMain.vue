<script>
import Preview from './Preview.vue';
import Test from './Test.vue';
import ReportGenerator from './ReportGenerator.vue';
import ParsingFile from './ParsingFile.vue';

export default {
    name: 'AppMain',
    components: {
        Preview, ReportGenerator, ParsingFile, Test
    },
}
</script>

<template>
    <div class="flex flex-col gap-4 justify-center items-center w-full p-4">
<!--        <Test></Test>-->
        <Preview></Preview>
        <ReportGenerator></ReportGenerator>
        <ParsingFile></ParsingFile>
    </div>
</template>
