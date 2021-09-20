<template>
    <div>
        <slot />
    </div>
</template>

<script>
import Navbar from "./components/Navbar";
export default {
name: "Layout",
    components: {Navbar}
}
</script>

<style scoped>

</style>
