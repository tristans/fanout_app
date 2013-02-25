// models
window.User = Backbone.Model.extend();

window.UserCollection = Backbone.Collection.extend({
    model: User,
    url: "user"
});

window.Activity = Backbone.Model.extend();

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

window.ActivityListView = Backbone.View.extend({
    tagName: "ul",
    initialize: function() {
        this.model.bind("reset", this.render, this);
    },
    render: function(eventName) {
        _.each(this.model.models, function(activity) {
            $(this.el).append(new ActivityListItemView({model: activity}).render().el);
        }, this);
        return this;
    }
});

window.ActivityListItemView = Backbone.View.extend({
    tagName: "li",
    template: _.template($("#tpl-activity-item").html()),
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
        this.activityList = new ActivityCollection();
        this.activityListView = new ActivityListView({model: this.activityList});
        this.activityList.fetch({data: {user_id: this.user.id, populate_usernames: true}});
        $("#activity-list").html(this.activityListView.render().el);
    }
});

var app = new AppRouter();
Backbone.history.start();
