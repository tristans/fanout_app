// models
Backbone.Relational.showWarnings = false;

window.User = Backbone.RelationalModel.extend({
    relations: [{
        type: Backbone.HasMany,
        key: 'activities',
        relatedModel: 'Activity',
        collectionType: 'ActivityCollection',
        reverseRelation: {
            key: 'user_id'
        }
    }]
});

window.UserCollection = Backbone.Collection.extend({
    model: User,
    url: "user"
});

window.Activity = Backbone.RelationalModel.extend();

window.ActivityCollection = Backbone.Collection.extend({
    model: Activity,
    url: "activity"
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
        "": "list",
        "users/:id": "userDetails",
        "activity/:id": "userActivity"
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
    },

    userActivity: function(id) {
        this.user = this.userList.get(id);
    }
});

var app = new AppRouter();
Backbone.history.start();
