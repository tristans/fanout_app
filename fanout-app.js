// models

window.User = Backbone.Model.extend();

window.UserCollection = Backbone.Collection.extend({
    model: User,
    url: "user"
});

window.UserListView = Backbone.View.extend({
    tagName: "ul",
    initialize: function() {
        this.model.bind("reset", this.render, this);
    },
    render: function(eventName) {
        _.each(this.model.models, function(user) {
            $(this.el).append(new UserListItemView({model:user}).render().el);
        }, this);

        return this;
    }
});

window.UserListItemView = Backbone.View.extend({
    tagName: "li",
    template: _.template($("#tpl-user-list-item").html()),
    render: function(eventName) {
        $(this.el).html(this.template(this.model.toJSON()));
        return this;
    }
});

window.UserView = Backbone.View.extend({
    template: _.template($("#tpl-user-details").html()),
    render: function(eventName) {
        $(this.el).html(this.template(this.model.toJSON()));
        return this;
    }
});

var AppRouter = Backbone.Router.extend({
    routes: {
        "":"list",
        "users/:id":"userDetails"
    },

    list: function() {
        this.userList = new UserCollection();
        this.userListView = new UserListView({model: this.userList});
        this.userList.fetch();
        $("#user-list").html(this.userListView.render().el);
    },

    userDetails: function(id) {
        this.user = this.userList.get(id);
        this.userView = new UserView({model: this.user});
        $("#user-content").html(this.userView.render().el);
    }
});

var app = new AppRouter();
Backbone.history.start();
