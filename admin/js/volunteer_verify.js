function confirmAction(action) {

    if (action === "verify") {
        return confirm("Are you sure you want to verify this volunteer?");
    }

    if (action === "reject") {
        return confirm("Are you sure you want to reject this volunteer?");
    }

    if (action === "delete") {
        return confirm("Are you sure you want to delete this volunteer?");
    }

    return true;
}